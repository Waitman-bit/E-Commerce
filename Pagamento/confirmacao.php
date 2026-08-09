<?php
/**
 * confirmacao.php
 *
 * Página somente leitura. Não cria nenhum pedido — apenas exibe o pedido
 * que já foi gravado em pagamento.php. Isso evita pedidos duplicados
 * caso o usuário atualize a página (padrão Post/Redirect/Get).
 */

session_start();
require_once('../connection.php');

function formatarPreco($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

if (!isset($_SESSION['id'])) {
    header('Location: ../Login/Login.php');
    exit;
}

if (!isset($_SESSION['ultimo_pedido_id'])) {
    header('Location: ../Index/Index.php');
    exit;
}

$idUsuario = (int) $_SESSION['id'];
$idPedido = (int) $_SESSION['ultimo_pedido_id'];

// Garante que o pedido pertence ao usuário logado (impede acessar pedido
// de outro cliente trocando o valor na sessão/URL).
$stmtPedido = $conn->prepare(
    'SELECT id_pedido, data_pedido, status_pedido, valor_total FROM pedido WHERE id_pedido = ? AND id_usuario = ?'
);
$stmtPedido->bind_param('ii', $idPedido, $idUsuario);
$stmtPedido->execute();
$pedido = $stmtPedido->get_result()->fetch_assoc();
$stmtPedido->close();

if (!$pedido) {
    header('Location: ../Index/Index.php');
    exit;
}

$stmtItens = $conn->prepare(
    'SELECT ip.quantidade, ip.preco_unitario, p.nome, p.imagem
     FROM item_pedido ip
     JOIN produto p ON p.id_produto = ip.id_produto
     WHERE ip.id_pedido = ?'
);
$stmtItens->bind_param('i', $idPedido);
$stmtItens->execute();
$itens = $stmtItens->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtItens->close();

$stmtEntrega = $conn->prepare('SELECT * FROM entrega WHERE id_pedido = ?');
$stmtEntrega->bind_param('i', $idPedido);
$stmtEntrega->execute();
$entrega = $stmtEntrega->get_result()->fetch_assoc();
$stmtEntrega->close();

$stmtPagamento = $conn->prepare('SELECT * FROM pagamento WHERE id_pedido = ?');
$stmtPagamento->bind_param('i', $idPedido);
$stmtPagamento->execute();
$pagamento = $stmtPagamento->get_result()->fetch_assoc();
$stmtPagamento->close();

$parcelas = [];
if ($pagamento) {
    $stmtParcelas = $conn->prepare('SELECT * FROM parcela WHERE id_pagamento = ? ORDER BY numero_parcela ASC');
    $stmtParcelas->bind_param('i', $pagamento['id_pagamento']);
    $stmtParcelas->execute();
    $parcelas = $stmtParcelas->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtParcelas->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - TitanSports</title>
    <link rel="stylesheet" href="confirmacao.css">
</head>
<body>

<div class="topo-checkout">
    <h1>Pedido Confirmado</h1>
    <a href="../Index/Index.php" class="link-voltar">&larr; Voltar à loja</a>
</div>

<div class="etapas-checkout">
    <span class="etapa">1. Checkout</span>
    <span class="etapa">2. Pagamento</span>
    <span class="etapa ativa">3. Confirmação</span>
</div>

<div class="container-confirmacao">

    <div class="card-checkout sucesso-box">
        <div class="icone-sucesso">✓</div>
        <h2>Pedido #<?php echo $pedido['id_pedido']; ?> realizado com sucesso!</h2>
        <p>Data: <?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?> · Status: <?php echo htmlspecialchars($pedido['status_pedido']); ?></p>
    </div>

    <div class="card-checkout">
        <h2>Itens do pedido</h2>
        <div class="lista-resumo-itens">
            <?php foreach ($itens as $item): ?>
                <div class="resumo-item">
                    <img src="../ImagensProdutos/<?php echo htmlspecialchars($item['imagem']); ?>" alt="<?php echo htmlspecialchars($item['nome']); ?>">
                    <div class="resumo-item-info">
                        <span class="resumo-item-nome"><?php echo htmlspecialchars($item['nome']); ?></span>
                        <span class="resumo-item-qtd">Qtd: <?php echo $item['quantidade']; ?></span>
                    </div>
                    <span class="resumo-item-preco"><?php echo formatarPreco($item['preco_unitario'] * $item['quantidade']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="resumo-linha resumo-total">
            <span>Total</span>
            <span><?php echo formatarPreco($pedido['valor_total']); ?></span>
        </div>
    </div>

    <div class="grid-duas-colunas">
        <?php if ($entrega): ?>
        <div class="card-checkout">
            <h2>Entrega</h2>
            <p><?php echo htmlspecialchars($entrega['endereco']); ?></p>
            <?php if ($entrega['cidade'] !== '-'): ?>
                <p><?php echo htmlspecialchars($entrega['cidade'] . ' - ' . $entrega['estado']); ?> · CEP <?php echo htmlspecialchars($entrega['cep']); ?></p>
            <?php endif; ?>
            <p>Status: <span class="tag"><?php echo htmlspecialchars($entrega['status']); ?></span></p>
            <p>Frete: <?php echo formatarPreco($entrega['frete']); ?></p>
        </div>
        <?php endif; ?>

        <?php if ($pagamento): ?>
        <div class="card-checkout">
            <h2>Pagamento</h2>
            <p>Forma: <?php echo htmlspecialchars($pagamento['tipo']); ?></p>
            <p>Status: <span class="tag tag-sucesso"><?php echo htmlspecialchars($pagamento['status']); ?></span></p>
            <?php if (!empty($parcelas)): ?>
                <p>Parcelamento:</p>
                <ul class="lista-parcelas">
                    <?php foreach ($parcelas as $parcela): ?>
                        <li>
                            <?php echo $parcela['numero_parcela']; ?>x
                            - <?php echo formatarPreco($parcela['valor_parcela']); ?>
                            - vence em <?php echo date('d/m/Y', strtotime($parcela['data_vencimento'])); ?>
                            - <?php echo htmlspecialchars($parcela['status']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <a href="../Index/Index.php" class="btn-continuar btn-link">Continuar comprando</a>
</div>

</body>
</html>
