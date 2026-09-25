<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../Checkout/frete.php';
require_once __DIR__ . '/../asaas_config.php';

function formatarPreco($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function validarCpfBrasileiro(string $cpf): bool
{
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) !== 11) {
        return false;
    }

    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += ((int) $cpf[$i]) * (10 - $i);
    }
    $digito1 = 11 - ($soma % 11);
    $digito1 = $digito1 >= 10 ? 0 : $digito1;

    if (((int) $cpf[9]) !== $digito1) {
        return false;
    }

    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += ((int) $cpf[$i]) * (11 - $i);
    }
    $digito2 = 11 - ($soma % 11);
    $digito2 = $digito2 >= 10 ? 0 : $digito2;

    return ((int) $cpf[10]) === $digito2;
}

function titan_estoque_disponivel(mysqli $conn, int $idProduto): int
{
    $stmt = $conn->prepare('SELECT COALESCE(SUM(estoque), 0) AS total FROM produto_tamanho WHERE id_produto = ?');
    $stmt->bind_param('i', $idProduto);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) ($resultado['total'] ?? 0);
}

function titan_baixar_estoque(mysqli $conn, int $idProduto, int $quantidade): void
{
    if ($quantidade <= 0) {
        return;
    }

    $totalDisponivel = titan_estoque_disponivel($conn, $idProduto);
    if ($totalDisponivel < $quantidade) {
        throw new Exception('Estoque insuficiente para este item.');
    }

    $stmt = $conn->prepare('SELECT id_tamanho, estoque FROM produto_tamanho WHERE id_produto = ? ORDER BY estoque DESC, id_tamanho ASC');
    $stmt->bind_param('i', $idProduto);
    $stmt->execute();
    $linhas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $restante = $quantidade;
    foreach ($linhas as $linha) {
        if ($restante <= 0) {
            break;
        }

        $disponivel = (int) ($linha['estoque'] ?? 0);
        if ($disponivel <= 0) {
            continue;
        }

        $retirar = min($restante, $disponivel);
        $idTamanho = (int) $linha['id_tamanho'];

        $update = $conn->prepare('UPDATE produto_tamanho SET estoque = estoque - ? WHERE id_produto = ? AND id_tamanho = ? AND estoque >= ?');
        $update->bind_param('iiii', $retirar, $idProduto, $idTamanho, $retirar);
        $update->execute();
        $afetadas = $update->affected_rows;
        $update->close();

        if ($afetadas !== 1) {
            throw new Exception('Não foi possível reservar o estoque do produto.');
        }

        $restante -= $retirar;
    }

    if ($restante > 0) {
        throw new Exception('Estoque insuficiente para este item.');
    }
}

function titan_asaas_obter_ou_criar_cliente(string $nome, string $cpf, string $email): string
{
    [, $busca] = titan_asaas_request('GET', '/customers?cpfCnpj=' . $cpf);
    if (!empty($busca['data'][0]['id'])) {
        return $busca['data'][0]['id'];
    }

    [$status, $criado] = titan_asaas_request('POST', '/customers', [
        'name'    => $nome,
        'cpfCnpj' => $cpf,
        'email'   => $email,
    ]);
    if ($status >= 300 || empty($criado['id'])) {
        throw new Exception('Erro ao cadastrar cliente no Asaas: ' . json_encode($criado));
    }

    return $criado['id'];
}

function titan_criar_pedido_pix(mysqli $conn, int $idUsuario): array
{
    $ck = $_SESSION['checkout'];

    $stmt = $conn->prepare('SELECT nome, cpf, email FROM usuario WHERE id_usuario = ?');
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$usuario) {
        throw new Exception('Usuário não encontrado.');
    }

    $cpf = preg_replace('/\D/', '', $usuario['cpf'] ?? '');
    if (!validarCpfBrasileiro($cpf)) {
        throw new Exception('CPF inválido no cadastro. Atualize seu perfil com um CPF real antes de pagar.');
    }

    $frete = titan_calcular_frete($ck['estado'], $ck['metodo_entrega']);
    if ($frete === false) {
        throw new Exception('Não foi possível calcular o frete.');
    }

    $conn->begin_transaction();
    try {
        // ===== ITENS + BAIXA DE ESTOQUE (preço vem do banco) =====
        $itens = [];
        $subtotal = 0.0;

        foreach ($_SESSION['carrinho'] as $idProduto => $itemSessao) {
            $idProduto = (int) $idProduto;
            $qtd = max(1, (int) $itemSessao['quantidade']);

            $stmt = $conn->prepare('SELECT nome, preco FROM produto WHERE id_produto = ?');
            $stmt->bind_param('i', $idProduto);
            $stmt->execute();
            $prod = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$prod) {
                continue;
            }

            titan_baixar_estoque($conn, $idProduto, $qtd);

            $preco = (float) $prod['preco'];
            $itens[] = ['id' => $idProduto, 'qtd' => $qtd, 'preco' => $preco];
            $subtotal += $preco * $qtd;
        }

        if (!$itens) {
            throw new Exception('Carrinho vazio.');
        }

        $total = round($subtotal + $frete['valor'], 2);

        // ===== PEDIDO =====
        $stmt = $conn->prepare("INSERT INTO pedido (valor_total, data_pedido, id_usuario, status_pedido, tipo_pagamento)
                                VALUES (?, NOW(), ?, 'Aguardando pagamento', 'pix')");
        $stmt->bind_param('di', $total, $idUsuario);
        $stmt->execute();
        $idPedido = $conn->insert_id;
        $stmt->close();

        // ===== ITEM_PEDIDO =====
        $stmt = $conn->prepare('INSERT INTO item_pedido (quantidade, preco_unitario, id_produto, id_pedido) VALUES (?, ?, ?, ?)');
        foreach ($itens as $i) {
            $stmt->bind_param('idii', $i['qtd'], $i['preco'], $i['id'], $idPedido);
            $stmt->execute();
        }
        $stmt->close();

        // ===== ENTREGA =====
        $endereco = $ck['logradouro'] . ', ' . $ck['numero'] . ($ck['complemento'] !== '' ? ' - ' . $ck['complemento'] : '');
        $stmt = $conn->prepare("INSERT INTO entrega (endereco, estado, cidade, cep, status, id_pedido, frete)
                                VALUES (?, ?, ?, ?, 'Pendente', ?, ?)");
        $stmt->bind_param('ssssid', $endereco, $ck['estado'], $ck['cidade'], $ck['cep'], $idPedido, $frete['valor']);
        $stmt->execute();
        $stmt->close();

        // ===== CLIENTE + COBRANÇA NO ASAAS =====
        $clienteId = titan_asaas_obter_ou_criar_cliente($usuario['nome'], $cpf, $usuario['email']);

        [$status, $cobranca] = titan_asaas_request('POST', '/payments', [
            'customer'    => $clienteId,
            'billingType' => 'PIX',
            'value'       => $total,
            'dueDate'     => date('Y-m-d'),
            'description' => 'Pedido #' . $idPedido . ' - TitanSports',
            'externalReference' => (string) $idPedido,
        ]);
        if ($status >= 300 || empty($cobranca['id'])) {
            throw new Exception('Erro ao gerar a cobrança: ' . json_encode($cobranca));
        }
        $paymentId = $cobranca['id'];

        [$statusQr, $qr] = titan_asaas_request('GET', '/payments/' . $paymentId . '/pixQrCode');
        if ($statusQr >= 300 || empty($qr['payload'])) {
            throw new Exception('Erro ao gerar o QR Code: ' . json_encode($qr));
        }

        // ===== CONTAS_RECEBER (1 parcela) =====
        $vencimento = date('Y-m-d H:i:s', strtotime($qr['expirationDate'] ?? '+1 day'));
        $stmt = $conn->prepare('INSERT INTO contas_receber (id_pedido, numero_parcela, valor_parcela, data_vencimento, asaas_payment_id)
                                VALUES (?, 1, ?, ?, ?)');
        $stmt->bind_param('idss', $idPedido, $total, $vencimento, $paymentId);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }

    return [
        'pedido'     => $idPedido,
        'payment_id' => $paymentId,
        'total'      => $total,
        'qr_base64'  => $qr['encodedImage'],
        'copia_cola' => $qr['payload'],
        'expira'     => date('H:i', strtotime($qr['expirationDate'] ?? '+1 day')),
    ];
}

// ===== GUARDAS =====
if (!isset($_SESSION['id'])) {
    header('Location: ../Login/Login.php');
    exit;
}
if (empty($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
    header('Location: ../Carrinho/carrinho.php');
    exit;
}
if (empty($_SESSION['checkout'])) {
    header('Location: ../Checkout/checkout.php');
    exit;
}

$erro = null;
$pix = $_SESSION['pedido_em_andamento'] ?? null;

if (!$pix) {
    try {
        $pix = titan_criar_pedido_pix($conn, (int) $_SESSION['id']);
        $_SESSION['pedido_em_andamento'] = $pix;
    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento - TitanSports</title>
    <link rel="stylesheet" href="../Checkout/checkout.css">
</head>
<body>

<div class="topo-checkout">
    <h1>Pagamento via Pix</h1>
    <a href="../Checkout/checkout.php" class="link-voltar">&larr; Voltar ao checkout</a>
</div>

<div class="etapas-checkout">
    <span class="etapa">1. Checkout</span>
    <span class="etapa ativa">2. Pagamento</span>
    <span class="etapa">3. Confirmação</span>
</div>

<?php if ($erro): ?>
    <div class="alerta alerta-erro"><?php echo htmlspecialchars($erro); ?></div>
<?php else: ?>
    <section class="card-checkout" style="max-width:480px;margin:0 auto;text-align:center;">
        <h2>Pedido #<?php echo (int) $pix['pedido']; ?></h2>
        <p>Total: <strong><?php echo formatarPreco($pix['total']); ?></strong></p>
        <img src="data:image/png;base64,<?php echo $pix['qr_base64']; ?>" alt="QR Code Pix" style="width:240px;height:240px;">
        <p>Ou copie o código:</p>
        <textarea id="pix" readonly rows="4" style="width:100%;"><?php echo htmlspecialchars($pix['copia_cola']); ?></textarea>
        <button type="button" class="btn-continuar" id="btnCopiar">Copiar código Pix</button>
        <p>Válido até <?php echo htmlspecialchars($pix['expira']); ?>. Aguardando pagamento...</p>
    </section>

    <script>
        document.getElementById('btnCopiar').addEventListener('click', function () {
            navigator.clipboard.writeText(document.getElementById('pix').value);
            this.textContent = 'Copiado!';
        });

        const paymentId = <?php echo json_encode($pix['payment_id']); ?>;
        const timer = setInterval(async function () {
            try {
                const r = await fetch('status.php?id=' + paymentId);
                const d = await r.json();
                if (d.status === 'approved') {
                    clearInterval(timer);
                    location.href = 'sucesso.php?pedido=' + d.pedido;
                }
            } catch (e) { /* tenta de novo no próximo ciclo */ }
        }, 5000);
    </script>
<?php endif; ?>

</body>
</html>