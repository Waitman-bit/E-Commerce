<?php
/**
 * checkout.php
 *
 * Etapa intermediária entre o Carrinho e o Pagamento.
 * - Confirma que o usuário está logado e que o carrinho não está vazio.
 * - Mostra/permite editar os dados de entrega.
 * - Calcula o frete (servidor) a partir da UF informada.
 * - Ao confirmar, guarda os dados de entrega em $_SESSION['checkout']
 *   e redireciona para ../Pagamento/pagamento.php.
 *
 * Não recria o carrinho: lê diretamente de $_SESSION['carrinho'].
 */

require_once('../connection.php');
require_once('frete.php');

function formatarPreco($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

// ===== 1. USUÁRIO PRECISA ESTAR LOGADO =====
if (!isset($_SESSION['id'])) {
    header('Location: ../Login/Login.php');
    exit;
}

// ===== 2. CARRINHO NÃO PODE ESTAR VAZIO =====
if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho']) || count($_SESSION['carrinho']) === 0) {
    header('Location: ../Carrinho/carrinho.php');
    exit;
}

$idUsuario = (int) $_SESSION['id'];

// ===== 3. BUSCA OS DADOS ATUAIS DO USUÁRIO =====
$stmt = $conn->prepare('SELECT nome, cpf, email, telefone, cep, numero, complemento FROM usuario WHERE id_usuario = ?');
$stmt->bind_param('i', $idUsuario);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) {
    // Sessão aponta para um usuário que não existe mais no banco.
    session_destroy();
    header('Location: ../Login/Login.php');
    exit;
}

// ===== 4. MONTA OS ITENS DO CARRINHO COM PREÇO ATUAL DO BANCO =====
// (o preço exibido aqui já vem do banco, para o cliente não ver um valor
// desatualizado; a validação definitiva de preço/estoque acontece em pagamento.php)
$itensCarrinho = [];
$subtotal = 0.0;
$avisoEstoque = [];

foreach ($_SESSION['carrinho'] as $idProduto => $itemSessao) {
    $stmtProd = $conn->prepare('SELECT id_produto, nome, preco, imagem, estoque FROM produto WHERE id_produto = ?');
    $stmtProd->bind_param('i', $idProduto);
    $stmtProd->execute();
    $produtoAtual = $stmtProd->get_result()->fetch_assoc();
    $stmtProd->close();

    if (!$produtoAtual) {
        continue; // produto removido do catálogo, ignora no checkout
    }

    $quantidade = max(1, (int) $itemSessao['quantidade']);

    if ($quantidade > (int) $produtoAtual['estoque']) {
        $avisoEstoque[] = $produtoAtual['nome'];
    }

    $precoUnitario = (float) $produtoAtual['preco'];

    $itensCarrinho[] = [
        'id'         => (int) $produtoAtual['id_produto'],
        'nome'       => $produtoAtual['nome'],
        'imagem'     => $produtoAtual['imagem'],
        'preco'      => $precoUnitario,
        'quantidade' => $quantidade,
        'subtotal'   => $precoUnitario * $quantidade,
    ];

    $subtotal += $precoUnitario * $quantidade;
}

// ===== 5. PROCESSA O FORMULÁRIO (POST) =====
$erros = [];
$dadosForm = [
    'cep'          => $usuario['cep'] ?? '',
    'logradouro'   => '',
    'numero'       => $usuario['numero'] ?? '',
    'complemento'  => $usuario['complemento'] ?? '',
    'cidade'       => '',
    'estado'       => '',
    'telefone'     => $usuario['telefone'] ?? '',
    'metodo_entrega' => 'normal',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dadosForm['cep']         = trim($_POST['cep'] ?? '');
    $dadosForm['logradouro']  = trim($_POST['logradouro'] ?? '');
    $dadosForm['numero']      = trim($_POST['numero'] ?? '');
    $dadosForm['complemento'] = trim($_POST['complemento'] ?? '');
    $dadosForm['cidade']      = trim($_POST['cidade'] ?? '');
    $dadosForm['estado']      = strtoupper(trim($_POST['estado'] ?? ''));
    $dadosForm['telefone']    = trim($_POST['telefone'] ?? '');
    $dadosForm['metodo_entrega'] = $_POST['metodo_entrega'] ?? '';

    $cepLimpo = preg_replace('/\D/', '', $dadosForm['cep']);

    if (strlen($cepLimpo) !== 8) {
        $erros[] = 'Informe um CEP válido (8 dígitos).';
    }

    if ($dadosForm['metodo_entrega'] !== 'retirada') {
        if ($dadosForm['logradouro'] === '') {
            $erros[] = 'Informe o endereço (rua/avenida).';
        }
        if ($dadosForm['numero'] === '') {
            $erros[] = 'Informe o número.';
        }
        if ($dadosForm['cidade'] === '') {
            $erros[] = 'Informe a cidade.';
        }
    }

    if (!array_key_exists($dadosForm['estado'], TITAN_MAPA_REGIAO_UF)) {
        $erros[] = 'Selecione um estado (UF) válido.';
    }

    if (!in_array($dadosForm['metodo_entrega'], ['normal', 'expressa', 'retirada'], true)) {
        $erros[] = 'Selecione um método de entrega válido.';
    }

    if (empty($avisoEstoque) && empty($erros)) {
        $freteCalculado = titan_calcular_frete($dadosForm['estado'], $dadosForm['metodo_entrega']);

        if ($freteCalculado === false) {
            $erros[] = 'Não foi possível calcular o frete para o estado informado.';
        }
    }

    if (empty($erros) && empty($avisoEstoque)) {
        // Estes campos pertencem ao cadastro do usuário (tabela `usuario`).
        // Se o cliente os alterar aqui no checkout, atualizamos o perfil dele
        // também, para que o telefone/CEP exibido no painel administrativo
        // fique sempre consistente com o que foi usado nesta entrega.
        $stmtAtualizaUsuario = $conn->prepare(
            'UPDATE usuario SET telefone = ?, numero = ?, complemento = ?, cep = ? WHERE id_usuario = ?'
        );
        $stmtAtualizaUsuario->bind_param(
            'ssssi',
            $dadosForm['telefone'],
            $dadosForm['numero'],
            $dadosForm['complemento'],
            $cepLimpo,
            $idUsuario
        );
        $stmtAtualizaUsuario->execute();
        $stmtAtualizaUsuario->close();

        $_SESSION['checkout'] = [
            'cep'            => $cepLimpo,
            'logradouro'     => $dadosForm['metodo_entrega'] === 'retirada' ? 'Retirada na loja' : $dadosForm['logradouro'],
            'numero'         => $dadosForm['metodo_entrega'] === 'retirada' ? '-' : $dadosForm['numero'],
            'complemento'    => $dadosForm['complemento'],
            'cidade'         => $dadosForm['metodo_entrega'] === 'retirada' ? '-' : $dadosForm['cidade'],
            'estado'         => $dadosForm['estado'],
            'telefone'       => $dadosForm['telefone'],
            'metodo_entrega' => $dadosForm['metodo_entrega'],
            'frete_valor'    => $freteCalculado['valor'],
            'frete_prazo'    => $freteCalculado['prazo'],
        ];

        header('Location: ../Pagamento/pagamento.php');
        exit;
    }
}

$listaUf = array_keys(TITAN_MAPA_REGIAO_UF);
sort($listaUf);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - TitanSports</title>
    <link rel="stylesheet" href="checkout.css">
</head>
<body>

<div class="topo-checkout">
    <h1>Finalizar Pedido</h1>
    <a href="../Carrinho/carrinho.php" class="link-voltar">&larr; Voltar ao carrinho</a>
</div>

<div class="etapas-checkout">
    <span class="etapa ativa">1. Checkout</span>
    <span class="etapa">2. Pagamento</span>
    <span class="etapa">3. Confirmação</span>
</div>

<?php if (!empty($avisoEstoque)): ?>
    <div class="alerta alerta-erro">
        Estoque insuficiente para: <?php echo htmlspecialchars(implode(', ', $avisoEstoque)); ?>.
        Volte ao carrinho e ajuste a quantidade.
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

<div class="container-checkout">

    <form method="POST" id="formCheckout" class="coluna-dados">

        <section class="card-checkout">
            <h2>Seus dados</h2>
            <div class="linha-dados">
                <div class="campo">
                    <label>Nome</label>
                    <input type="text" value="<?php echo htmlspecialchars($usuario['nome']); ?>" disabled>
                </div>
                <div class="campo">
                    <label>E-mail</label>
                    <input type="text" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" disabled>
                </div>
            </div>
            <div class="linha-dados">
                <div class="campo">
                    <label>CPF</label>
                    <input type="text" value="<?php echo htmlspecialchars($usuario['cpf'] ?? 'Não informado'); ?>" disabled>
                </div>
                <div class="campo">
                    <label>Telefone</label>
                    <input type="text" name="telefone" id="telefone" value="<?php echo htmlspecialchars($dadosForm['telefone']); ?>" placeholder="(16) 99999-9999">
                </div>
            </div>
        </section>

        <section class="card-checkout">
            <h2>Endereço de entrega</h2>

            <div class="linha-dados">
                <div class="campo campo-cep">
                    <label>CEP</label>
                    <input type="text" name="cep" id="cep" maxlength="9" placeholder="00000-000" value="<?php echo htmlspecialchars($dadosForm['cep']); ?>" required>
                    <span id="statusCep" class="status-cep"></span>
                </div>
                <div class="campo">
                    <label>Endereço</label>
                    <input type="text" name="logradouro" id="logradouro" placeholder="Rua, avenida..." value="<?php echo htmlspecialchars($dadosForm['logradouro']); ?>">
                </div>
            </div>

            <div class="linha-dados">
                <div class="campo campo-pequeno">
                    <label>Número</label>
                    <input type="text" name="numero" id="numero" value="<?php echo htmlspecialchars($dadosForm['numero']); ?>">
                </div>
                <div class="campo">
                    <label>Complemento (opcional)</label>
                    <input type="text" name="complemento" id="complemento" value="<?php echo htmlspecialchars($dadosForm['complemento']); ?>">
                </div>
            </div>

            <div class="linha-dados">
                <div class="campo">
                    <label>Cidade</label>
                    <input type="text" name="cidade" id="cidade" value="<?php echo htmlspecialchars($dadosForm['cidade']); ?>">
                </div>
                <div class="campo campo-pequeno">
                    <label>Estado</label>
                    <select name="estado" id="estado">
                        <option value="">UF</option>
                        <?php foreach ($listaUf as $uf): ?>
                            <option value="<?php echo $uf; ?>" <?php echo $dadosForm['estado'] === $uf ? 'selected' : ''; ?>>
                                <?php echo $uf; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </section>

        <section class="card-checkout">
            <h2>Método de entrega</h2>
            <div id="opcoesEntrega" class="opcoes-entrega">
                <label class="opcao-entrega">
                    <input type="radio" name="metodo_entrega" value="normal" <?php echo $dadosForm['metodo_entrega'] === 'normal' ? 'checked' : ''; ?>>
                    <span class="opcao-titulo">Entrega Normal</span>
                    <span class="opcao-detalhe" data-metodo="normal">Informe o CEP para ver valor e prazo</span>
                </label>
                <label class="opcao-entrega">
                    <input type="radio" name="metodo_entrega" value="expressa" <?php echo $dadosForm['metodo_entrega'] === 'expressa' ? 'checked' : ''; ?>>
                    <span class="opcao-titulo">Entrega Expressa</span>
                    <span class="opcao-detalhe" data-metodo="expressa">Informe o CEP para ver valor e prazo</span>
                </label>
                <label class="opcao-entrega">
                    <input type="radio" name="metodo_entrega" value="retirada" <?php echo $dadosForm['metodo_entrega'] === 'retirada' ? 'checked' : ''; ?>>
                    <span class="opcao-titulo">Retirar na loja</span>
                    <span class="opcao-detalhe" data-metodo="retirada">Grátis</span>
                </label>
            </div>
        </section>

        <button type="submit" class="btn-continuar" <?php echo !empty($avisoEstoque) ? 'disabled' : ''; ?>>
            Continuar para pagamento
        </button>
    </form>

    <aside class="coluna-resumo">
        <div class="card-checkout resumo-pedido">
            <h2>Seu pedido</h2>
            <div class="lista-resumo-itens">
                <?php foreach ($itensCarrinho as $item): ?>
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

            <div class="resumo-linha">
                <span>Subtotal</span>
                <span id="valorSubtotal"><?php echo formatarPreco($subtotal); ?></span>
            </div>
            <div class="resumo-linha">
                <span>Frete</span>
                <span id="valorFrete">--</span>
            </div>
            <div class="resumo-linha resumo-total">
                <span>Total</span>
                <span id="valorTotal"><?php echo formatarPreco($subtotal); ?></span>
            </div>
        </div>
    </aside>

</div>

<script>
    const SUBTOTAL_CARRINHO = <?php echo json_encode($subtotal); ?>;
</script>
<script src="checkout.js"></script>
</body>
</html>
