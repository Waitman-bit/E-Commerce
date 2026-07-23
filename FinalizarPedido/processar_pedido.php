<?php
/**
 * processar_pedido.php
 *
 * Recebe o POST do checkout.php, revalida tudo no servidor (nunca confia
 * apenas no que veio do cliente), grava o pedido no banco dentro de uma
 * transação e limpa o carrinho ao final.
 *
 * Tabelas usadas (todas já existentes, nenhuma nova): pedido, item_pedido,
 * entrega, pagamento, produto.
 */

session_start();
require_once('../connection.php');
require_once('frete.php');

if (!isset($_SESSION['id'])) {
    header('Location: ../Login/Login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho']) || empty($_SESSION['carrinho'])) {
    header('Location: ../Carrinho/carrinho.php');
    exit;
}

$idUsuario = (int) $_SESSION['id'];
$carrinho = $_SESSION['carrinho'];

/* =====================================================
   1) VALIDAÇÃO DOS DADOS DO FORMULÁRIO (defesa no servidor)
   ===================================================== */
$erros = [];

$cpf            = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$email          = trim($_POST['email'] ?? '');
$telefone       = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
$cep            = preg_replace('/\D/', '', $_POST['cep'] ?? '');
$nomeCompleto   = trim($_POST['nome_completo'] ?? '');
$rua            = trim($_POST['rua'] ?? '');
$numero         = trim($_POST['numero'] ?? '');
$complemento    = trim($_POST['complemento'] ?? '');
$bairro         = trim($_POST['bairro'] ?? '');
$cidade         = trim($_POST['cidade'] ?? '');
$estado         = trim($_POST['estado'] ?? '');
$tipoEntrega    = $_POST['tipo_entrega'] ?? '';
$formaPagamento = $_POST['forma_pagamento'] ?? '';
$cupomCodigo    = trim($_POST['cupom_codigo'] ?? '');

if ($nomeCompleto === '') $erros[] = 'Nome completo é obrigatório.';
if (strlen($cpf) !== 11) $erros[] = 'CPF inválido.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido.';
if (strlen($telefone) < 10) $erros[] = 'Telefone inválido.';
if (strlen($cep) !== 8) $erros[] = 'CEP inválido.';
if ($rua === '' || $numero === '' || $bairro === '' || $cidade === '' || $estado === '') $erros[] = 'Endereço incompleto.';
if (!in_array($tipoEntrega, ['padrao', 'expressa', 'retirada'], true)) $erros[] = 'Método de entrega inválido.';
if (!in_array($formaPagamento, ['cartao_credito', 'cartao_debito', 'pix', 'boleto'], true)) $erros[] = 'Forma de pagamento inválida.';

if (in_array($formaPagamento, ['cartao_credito', 'cartao_debito'], true)) {
    $numeroCartao   = preg_replace('/\D/', '', $_POST['numero_cartao'] ?? '');
    $nomeCartao     = trim($_POST['nome_cartao'] ?? '');
    $validadeCartao = trim($_POST['validade_cartao'] ?? '');
    $cvv            = trim($_POST['cvv'] ?? '');

    if (strlen($numeroCartao) < 13 || strlen($numeroCartao) > 19) $erros[] = 'Número de cartão inválido.';
    if ($nomeCartao === '') $erros[] = 'Nome impresso no cartão é obrigatório.';
    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $validadeCartao)) $erros[] = 'Validade do cartão inválida.';
    if (!preg_match('/^\d{3,4}$/', $cvv)) $erros[] = 'CVV inválido.';
}

if (!empty($erros)) {
    $_SESSION['checkout_erro'] = implode(' ', $erros);
    header('Location: checkout.php');
    exit;
}

/* =====================================================
   2) RECALCULA O FRETE NO SERVIDOR
   (nunca confiar apenas no valor enviado pelo formulário)
   ===================================================== */
$resultadoFrete = titan_calcularFrete($cep, $tipoEntrega);

if ($resultadoFrete['erro'] !== null) {
    $_SESSION['checkout_erro'] = $resultadoFrete['erro'];
    header('Location: checkout.php');
    exit;
}

$valorFrete = $resultadoFrete['valor'];

/* =====================================================
   3) RECALCULA O SUBTOTAL A PARTIR DO CARRINHO + PREÇOS REAIS DO BANCO
   ===================================================== */
$subtotal = 0;
$itensValidados = [];

foreach ($carrinho as $idProduto => $item) {
    $stmt = $conn->prepare('SELECT id_produto, nome, preco, estoque FROM produto WHERE id_produto = ?');
    $stmt->bind_param('i', $idProduto);
    $stmt->execute();
    $produtoDb = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$produtoDb) {
        continue; // produto pode ter sido removido do catálogo
    }

    $quantidade = (int) $item['quantidade'];

    if ($quantidade > (int) $produtoDb['estoque']) {
        $_SESSION['checkout_erro'] = 'Estoque insuficiente para "' . $produtoDb['nome'] . '".';
        header('Location: ../Carrinho/carrinho.php');
        exit;
    }

    $precoUnitario = (float) $produtoDb['preco'];
    $subtotal += $precoUnitario * $quantidade;

    $itensValidados[] = [
        'id_produto'     => (int) $produtoDb['id_produto'],
        'quantidade'     => $quantidade,
        'preco_unitario' => $precoUnitario,
    ];
}

if (empty($itensValidados)) {
    $_SESSION['checkout_erro'] = 'Não foi possível processar os itens do carrinho.';
    header('Location: ../Carrinho/carrinho.php');
    exit;
}

/* =====================================================
   4) CUPOM SIMULADO
   Não existe tabela "cupom" no banco (conforme informado).
   Esta lista espelha a do checkout.js e deve ser substituída
   por consulta real assim que a tabela existir.
   ===================================================== */
$cuponsValidos = [
    'TITAN10'     => 0.10, // 10% de desconto no subtotal
    'FRETEGRATIS' => null, // frete grátis, tratado à parte
];

$desconto = 0.0;
$codigoNormalizado = strtoupper($cupomCodigo);

if ($cupomCodigo !== '' && array_key_exists($codigoNormalizado, $cuponsValidos)) {
    if ($codigoNormalizado === 'FRETEGRATIS') {
        $valorFrete = 0.0;
    } else {
        $desconto = round($subtotal * $cuponsValidos[$codigoNormalizado], 2);
    }
}

$valorTotal = round($subtotal + $valorFrete - $desconto, 2);

/* =====================================================
   5) STATUS CONFORME FORMA DE PAGAMENTO (compra simulada)
   ===================================================== */
if (in_array($formaPagamento, ['cartao_credito', 'cartao_debito'], true)) {
    $statusPagamento = 'Aprovado';
    $statusPedido = 'Confirmado';
} else {
    // PIX e boleto ficam pendentes até confirmação (simulação)
    $statusPagamento = 'Aguardando pagamento';
    $statusPedido = 'Aguardando pagamento';
}

$statusEntrega = $tipoEntrega === 'retirada' ? 'Aguardando retirada' : 'Aguardando envio';

/* =====================================================
   6) GRAVA TUDO EM UMA TRANSAÇÃO
   pedido -> item_pedido (+ baixa de estoque) -> entrega -> pagamento
   ===================================================== */
$conn->begin_transaction();

try {
    $stmt = $conn->prepare('INSERT INTO pedido (data_pedido, status_pedido, valor_total, id_usuario) VALUES (NOW(), ?, ?, ?)');
    $stmt->bind_param('sdi', $statusPedido, $valorTotal, $idUsuario);
    $stmt->execute();
    $idPedido = $stmt->insert_id;
    $stmt->close();

    $stmtItem = $conn->prepare('INSERT INTO item_pedido (id_produto, id_pedido, quantidade, preco_unitario) VALUES (?, ?, ?, ?)');
    $stmtEstoque = $conn->prepare('UPDATE produto SET estoque = estoque - ? WHERE id_produto = ? AND estoque >= ?');

    foreach ($itensValidados as $item) {
        $stmtItem->bind_param('iiid', $item['id_produto'], $idPedido, $item['quantidade'], $item['preco_unitario']);
        $stmtItem->execute();

        $stmtEstoque->bind_param('iii', $item['quantidade'], $item['id_produto'], $item['quantidade']);
        $stmtEstoque->execute();

        if ($stmtEstoque->affected_rows === 0) {
            throw new Exception('Estoque insuficiente durante a finalização da compra.');
        }
    }

    $stmtItem->close();
    $stmtEstoque->close();

    $enderecoCompleto = $rua . ', ' . $numero . ($complemento !== '' ? ' - ' . $complemento : '') . ' - ' . $bairro;
    $cepFormatado = substr($cep, 0, 5) . '-' . substr($cep, 5);

    $stmt = $conn->prepare('INSERT INTO entrega (endereco, estado, cidade, cep, status, id_pedido, frete) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('sssssid', $enderecoCompleto, $estado, $cidade, $cepFormatado, $statusEntrega, $idPedido, $valorFrete);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('INSERT INTO pagamento (tipo, valor, status, id_pedido) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('sdsi', $formaPagamento, $valorTotal, $statusPagamento, $idPedido);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['checkout_erro'] = 'Não foi possível finalizar o pedido. Tente novamente.';
    header('Location: checkout.php');
    exit;
}

/* =====================================================
   7) LIMPA O CARRINHO E REDIRECIONA PARA A CONFIRMAÇÃO
   ===================================================== */
unset($_SESSION['carrinho']);

header('Location: pedido_confirmado.php?id=' . $idPedido);
exit;
