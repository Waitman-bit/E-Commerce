<?php
/**
 * pagamento.php
 *
 * Etapa final da compra. Depende de $_SESSION['checkout'] (definida em
 * ../Checkout/checkout.php) e de $_SESSION['carrinho'].
 *
 * Ao confirmar o pagamento:
 *  1. Revalida estoque e preço de cada item direto no banco.
 *  2. Revalida o frete no servidor (nunca confia no valor do navegador).
 *  3. Registra pedido, item_pedido, entrega, pagamento e parcela (se houver)
 *     dentro de uma transação MySQL.
 *  4. Reduz o estoque.
 *  5. Limpa o carrinho e os dados de checkout da sessão.
 *  6. Redireciona para confirmacao.php (padrão Post/Redirect/Get, evita
 *     pedido duplicado se o usuário atualizar a página).
 *
 * PAGAMENTO SIMULADO: não há integração real com gateway de pagamento.
 * Nenhum dado sensível de cartão (número completo ou CVV) é enviado ao
 * servidor ou salvo no banco — os campos de cartão no formulário servem
 * apenas para compor a experiência visual do checkout.
 */

session_start();
require_once('../connection.php');
require_once('../Checkout/frete.php');

function formatarPreco($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

// ===== 1. VALIDAÇÕES DE ACESSO =====
if (!isset($_SESSION['id'])) {
    header('Location: ../Login/Login.php');
    exit;
}

if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho']) || count($_SESSION['carrinho']) === 0) {
    header('Location: ../Carrinho/carrinho.php');
    exit;
}

if (!isset($_SESSION['checkout']) || !is_array($_SESSION['checkout'])) {
    header('Location: ../Checkout/checkout.php');
    exit;
}

$idUsuario = (int) $_SESSION['id'];
$checkoutSessao = $_SESSION['checkout'];

// ===== 2. MONTA RESUMO ATUAL (para exibição) A PARTIR DO BANCO =====
function montarResumoPedido($conn, $carrinhoSessao, $checkoutSessao)
{
    $itens = [];
    $subtotal = 0.0;
    $erro = null;

    foreach ($carrinhoSessao as $idProduto => $itemSessao) {
        $stmt = $conn->prepare('SELECT id_produto, nome, preco, imagem, estoque FROM produto WHERE id_produto = ? FOR UPDATE');
        // FOR UPDATE só tem efeito dentro de uma transação; fora dela o MySQLi
        // simplesmente ignora o lock (usado aqui de forma inofensiva também
        // para a pré-visualização).
        $stmt->bind_param('i', $idProduto);
        $stmt->execute();
        $produto = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$produto) {
            $erro = 'Um dos produtos do carrinho não está mais disponível.';
            break;
        }

        $quantidade = max(1, (int) $itemSessao['quantidade']);

        if ($quantidade > (int) $produto['estoque']) {
            $erro = 'Estoque insuficiente para "' . $produto['nome'] . '".';
            break;
        }

        $precoUnitario = (float) $produto['preco'];

        $itens[] = [
            'id'         => (int) $produto['id_produto'],
            'nome'       => $produto['nome'],
            'imagem'     => $produto['imagem'],
            'preco'      => $precoUnitario,
            'quantidade' => $quantidade,
            'subtotal'   => $precoUnitario * $quantidade,
        ];

        $subtotal += $precoUnitario * $quantidade;
    }

    if ($erro) {
        return ['erro' => $erro];
    }

    $frete = titan_calcular_frete($checkoutSessao['estado'], $checkoutSessao['metodo_entrega']);

    if ($frete === false) {
        return ['erro' => 'Não foi possível recalcular o frete. Volte ao checkout.'];
    }

    return [
        'itens'     => $itens,
        'subtotal'  => $subtotal,
        'frete'     => $frete['valor'],
        'prazo'     => $frete['prazo'],
        'total'     => $subtotal + $frete['valor'],
    ];
}

$resumo = montarResumoPedido($conn, $_SESSION['carrinho'], $checkoutSessao);

if (isset($resumo['erro'])) {
    $erroResumo = $resumo['erro'];
} else {
    $erroResumo = null;
}

// ===== 3. PROCESSA O PAGAMENTO (POST) =====
$erros = [];
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erroResumo === null) {

    $formaPagamento = $_POST['forma_pagamento'] ?? '';
    $parcelas = isset($_POST['parcelas']) ? max(1, min(12, (int) $_POST['parcelas'])) : 1;

    $formasValidas = ['credito', 'debito', 'pix'];

    if (!in_array($formaPagamento, $formasValidas, true)) {
        $erros[] = 'Selecione uma forma de pagamento válida.';
    }

    if ($formaPagamento !== 'credito') {
        $parcelas = 1;
    }

    // Revalida tudo de novo, imediatamente antes de gravar (defesa contra
    // condição de corrida entre a exibição da página e o clique em confirmar).
    $resumoFinal = montarResumoPedido($conn, $_SESSION['carrinho'], $checkoutSessao);

    if (isset($resumoFinal['erro'])) {
        $erros[] = $resumoFinal['erro'];
    }

    if (empty($erros)) {
        $conn->begin_transaction();

        try {
            $dataPedido = date('Y-m-d H:i:s');
            $statusPedido = 'Pago'; // pagamento simulado sempre "aprovado"

            $stmtPedido = $conn->prepare(
                'INSERT INTO pedido (data_pedido, status_pedido, valor_total, id_usuario) VALUES (?, ?, ?, ?)'
            );
            $stmtPedido->bind_param('ssdi', $dataPedido, $statusPedido, $resumoFinal['total'], $idUsuario);
            $stmtPedido->execute();
            $idPedido = $conn->insert_id;
            $stmtPedido->close();

            // ---- Itens do pedido + baixa de estoque ----
            $stmtItem = $conn->prepare(
                'INSERT INTO item_pedido (id_produto, id_pedido, quantidade, preco_unitario) VALUES (?, ?, ?, ?)'
            );
            $stmtEstoque = $conn->prepare(
                'UPDATE produto SET estoque = estoque - ? WHERE id_produto = ? AND estoque >= ?'
            );

            foreach ($resumoFinal['itens'] as $item) {
                $stmtItem->bind_param('iiid', $item['id'], $idPedido, $item['quantidade'], $item['preco']);
                $stmtItem->execute();

                $stmtEstoque->bind_param('iii', $item['quantidade'], $item['id'], $item['quantidade']);
                $stmtEstoque->execute();

                if ($stmtEstoque->affected_rows === 0) {
                    throw new Exception('Estoque insuficiente para "' . $item['nome'] . '" no momento da confirmação.');
                }
            }
            $stmtItem->close();
            $stmtEstoque->close();

            // ---- Entrega ----
            $enderecoFinal = $checkoutSessao['metodo_entrega'] === 'retirada'
                ? 'Retirada na loja'
                : trim($checkoutSessao['logradouro'] . ', ' . $checkoutSessao['numero']
                    . ($checkoutSessao['complemento'] !== '' ? ' - ' . $checkoutSessao['complemento'] : ''));

            $statusEntrega = $checkoutSessao['metodo_entrega'] === 'retirada' ? 'Aguardando retirada' : 'Aguardando envio';
            $cepFormatado = substr($checkoutSessao['cep'], 0, 5) . '-' . substr($checkoutSessao['cep'], 5);

            $stmtEntrega = $conn->prepare(
                'INSERT INTO entrega (endereco, estado, cidade, cep, status, id_pedido, frete) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmtEntrega->bind_param(
                'sssssid',
                $enderecoFinal,
                $checkoutSessao['estado'],
                $checkoutSessao['cidade'],
                $cepFormatado,
                $statusEntrega,
                $idPedido,
                $resumoFinal['frete']
            );
            $stmtEntrega->execute();
            $stmtEntrega->close();

            // ---- Pagamento ----
            $tipoPagamentoTexto = [
                'credito' => 'Cartão de Crédito',
                'debito'  => 'Cartão de Débito',
                'pix'     => 'Pix',
            ][$formaPagamento];

            $statusPagamento = 'Aprovado';

            $stmtPagamento = $conn->prepare(
                'INSERT INTO pagamento (tipo, valor, status, id_pedido) VALUES (?, ?, ?, ?)'
            );
            $stmtPagamento->bind_param('sdsi', $tipoPagamentoTexto, $resumoFinal['total'], $statusPagamento, $idPedido);
            $stmtPagamento->execute();
            $idPagamento = $conn->insert_id;
            $stmtPagamento->close();

            // ---- Parcelas (somente cartão de crédito) ----
            if ($formaPagamento === 'credito' && $parcelas > 1) {
                $valorBase = floor(($resumoFinal['total'] / $parcelas) * 100) / 100;
                $somaParcelas = $valorBase * ($parcelas - 1);
                $ultimaParcela = round($resumoFinal['total'] - $somaParcelas, 2);

                $stmtParcela = $conn->prepare(
                    'INSERT INTO parcela (id_pagamento, valor_parcela, status, numero_parcela, data_vencimento) VALUES (?, ?, ?, ?, ?)'
                );

                for ($i = 1; $i <= $parcelas; $i++) {
                    $valorParcela = ($i === $parcelas) ? $ultimaParcela : $valorBase;
                    $statusParcela = ($i === 1) ? 'Paga' : 'Pendente';
                    $vencimento = date('Y-m-d', strtotime("+{$i} month", strtotime($dataPedido)));

                    $stmtParcela->bind_param('idsis', $idPagamento, $valorParcela, $statusParcela, $i, $vencimento);
                    $stmtParcela->execute();
                }
                $stmtParcela->close();
            } elseif ($formaPagamento === 'credito') {
                // à vista no crédito = 1 parcela paga
                $stmtParcela = $conn->prepare(
                    'INSERT INTO parcela (id_pagamento, valor_parcela, status, numero_parcela, data_vencimento) VALUES (?, ?, ?, ?, ?)'
                );
                $statusParcela = 'Paga';
                $numeroParcela = 1;
                $vencimento = date('Y-m-d', strtotime($dataPedido));
                $stmtParcela->bind_param('idsis', $idPagamento, $resumoFinal['total'], $statusParcela, $numeroParcela, $vencimento);
                $stmtParcela->execute();
                $stmtParcela->close();
            }

            $conn->commit();

            // ---- Limpeza da sessão ----
            unset($_SESSION['carrinho']);
            unset($_SESSION['checkout']);
            $_SESSION['ultimo_pedido_id'] = $idPedido;

            header('Location: confirmacao.php');
            exit;

        } catch (Throwable $e) {
            $conn->rollback();
            $erros[] = 'Não foi possível concluir o pedido: ' . $e->getMessage();
        }
    }
}

$listaOpcoesParcelas = [];
if (!isset($resumo['erro'])) {
    for ($i = 1; $i <= 12; $i++) {
        $valorParcela = $resumo['total'] / $i;
        $listaOpcoesParcelas[] = [
            'numero' => $i,
            'valor'  => $valorParcela,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento - TitanSports</title>
    <link rel="stylesheet" href="pagamento.css">
</head>
<body>

<div class="topo-checkout">
    <h1>Pagamento</h1>
    <a href="../Checkout/checkout.php" class="link-voltar">&larr; Voltar ao checkout</a>
</div>

<div class="etapas-checkout">
    <span class="etapa">1. Checkout</span>
    <span class="etapa ativa">2. Pagamento</span>
    <span class="etapa">3. Confirmação</span>
</div>

<?php if ($erroResumo): ?>
    <div class="alerta alerta-erro">
        <?php echo htmlspecialchars($erroResumo); ?>
        <br><a href="../Checkout/checkout.php" style="color:#fff;">Voltar ao checkout</a>
    </div>
<?php endif; ?>

<?php if (!empty($erros)): ?>
    <div class="alerta alerta-erro">
        <ul>
            <?php foreach ($erros as $erro): ?>
                <li><?php echo htmlspecialchars($erro); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!$erroResumo): ?>
<div class="container-checkout">

    <form method="POST" id="formPagamento" class="coluna-dados">

        <section class="card-checkout">
            <h2>Forma de pagamento</h2>
            <div class="abas-pagamento">
                <label class="aba-pagamento">
                    <input type="radio" name="forma_pagamento" value="credito" checked>
                    <span>Cartão de Crédito</span>
                </label>
                <label class="aba-pagamento">
                    <input type="radio" name="forma_pagamento" value="debito">
                    <span>Cartão de Débito</span>
                </label>
                <label class="aba-pagamento">
                    <input type="radio" name="forma_pagamento" value="pix">
                    <span>Pix</span>
                </label>
            </div>

            <div id="painelCredito" class="painel-pagamento">
                <div class="linha-dados">
                    <div class="campo">
                        <label>Nome no cartão</label>
                        <input type="text" id="nomeCartao" placeholder="Como está impresso no cartão">
                    </div>
                </div>
                <div class="linha-dados">
                    <div class="campo">
                        <label>Número do cartão</label>
                        <input type="text" id="numeroCartao" placeholder="0000 0000 0000 0000" maxlength="19" autocomplete="off">
                    </div>
                    <div class="campo campo-pequeno">
                        <label>Validade</label>
                        <input type="text" id="validadeCartao" placeholder="MM/AA" maxlength="5">
                    </div>
                    <div class="campo campo-pequeno">
                        <label>CVV</label>
                        <input type="password" id="cvvCartao" placeholder="123" maxlength="4" autocomplete="off">
                    </div>
                </div>
                <div class="linha-dados">
                    <div class="campo">
                        <label>Parcelamento</label>
                        <select name="parcelas" id="parcelas">
                            <?php foreach ($listaOpcoesParcelas as $opcao): ?>
                                <option value="<?php echo $opcao['numero']; ?>">
                                    <?php echo $opcao['numero']; ?>x de <?php echo formatarPreco($opcao['valor']); ?>
                                    <?php echo $opcao['numero'] === 1 ? '(à vista)' : 'sem juros'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <p class="nota-seguranca">🔒 Ambiente de simulação. Nenhum número de cartão ou CVV é enviado ao servidor.</p>
            </div>

            <div id="painelDebito" class="painel-pagamento" style="display:none;">
                <div class="linha-dados">
                    <div class="campo">
                        <label>Nome no cartão</label>
                        <input type="text" placeholder="Como está impresso no cartão">
                    </div>
                </div>
                <div class="linha-dados">
                    <div class="campo">
                        <label>Número do cartão</label>
                        <input type="text" placeholder="0000 0000 0000 0000" maxlength="19" autocomplete="off">
                    </div>
                    <div class="campo campo-pequeno">
                        <label>Validade</label>
                        <input type="text" placeholder="MM/AA" maxlength="5">
                    </div>
                    <div class="campo campo-pequeno">
                        <label>CVV</label>
                        <input type="password" placeholder="123" maxlength="4" autocomplete="off">
                    </div>
                </div>
                <p class="nota-seguranca">🔒 Ambiente de simulação. Nenhum número de cartão ou CVV é enviado ao servidor.</p>
            </div>

            <div id="painelPix" class="painel-pagamento" style="display:none;">
                <div class="pix-box">
                    <div class="pix-qr">QR CODE<br>(simulado)</div>
                    <p>Escaneie o QR Code ou use o código Pix Copia e Cola abaixo:</p>
                    <div class="pix-codigo">00020126TITANSPORTS-SIMULADO-PIX5204000053039865802BR</div>
                    <p class="nota-seguranca">🔒 Ambiente de simulação — a aprovação é imediata para fins de teste.</p>
                </div>
            </div>
        </section>

        <button type="submit" class="btn-continuar">Confirmar pagamento</button>
    </form>

    <aside class="coluna-resumo">
        <div class="card-checkout resumo-pedido">
            <h2>Resumo da compra</h2>

            <div class="lista-resumo-itens">
                <?php foreach ($resumo['itens'] as $item): ?>
                    <div class="resumo-item">
                        <img src="../ImagensProdutos/<?php echo htmlspecialchars($item['imagem']); ?>" alt="<?php echo htmlspecialchars($item['nome']); ?>">
                        <div class="resumo-item-info">
                            <span class="resumo-item-nome"><?php echo htmlspecialchars($item['nome']); ?></span>
                            <span class="resumo-item-qtd">Qtd: <?php echo $item['quantidade']; ?></span>
                        </div>
                        <span class="resumo-item-preco"><?php echo formatarPreco($item['subtotal']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="resumo-linha"><span>Subtotal</span><span><?php echo formatarPreco($resumo['subtotal']); ?></span></div>
            <div class="resumo-linha"><span>Frete (<?php echo htmlspecialchars($checkoutSessao['metodo_entrega']); ?>)</span><span><?php echo formatarPreco($resumo['frete']); ?></span></div>
            <div class="resumo-linha"><span>Prazo estimado</span><span><?php echo htmlspecialchars($resumo['prazo']); ?></span></div>
            <div class="resumo-linha resumo-total"><span>Total</span><span><?php echo formatarPreco($resumo['total']); ?></span></div>

            <div class="resumo-endereco">
                <strong>Entregar em:</strong>
                <p>
                    <?php if ($checkoutSessao['metodo_entrega'] === 'retirada'): ?>
                        Retirada na loja
                    <?php else: ?>
                        <?php echo htmlspecialchars($checkoutSessao['logradouro'] . ', ' . $checkoutSessao['numero']); ?><br>
                        <?php echo htmlspecialchars($checkoutSessao['cidade'] . ' - ' . $checkoutSessao['estado']); ?><br>
                        CEP: <?php echo htmlspecialchars($checkoutSessao['cep']); ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </aside>
</div>
<?php endif; ?>

<script>
    const TOTAL_COMPRA = <?php echo json_encode($resumo['total'] ?? 0); ?>;
</script>
<script src="pagamento.js"></script>
</body>
</html>
