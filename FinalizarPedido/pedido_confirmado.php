<?php
/**
 * pedido_confirmado.php
 *
 * Exibe a confirmação do pedido recém-criado, lendo diretamente do banco
 * (pedido + entrega + pagamento + item_pedido) para garantir que os dados
 * mostrados são exatamente os que foram salvos.
 */

session_start();
require_once('../connection.php');

if (!isset($_SESSION['id'])) {
    header('Location: ../Login/Login.php');
    exit;
}

$idPedido = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idPedido <= 0) {
    header('Location: ../Index/Index.php');
    exit;
}

// Garante que o pedido pertence ao usuário logado
$stmt = $conn->prepare('
    SELECT p.id_pedido, p.data_pedido, p.status_pedido, p.valor_total,
           e.endereco, e.cidade, e.estado, e.cep, e.frete, e.status AS status_entrega,
           pg.tipo AS forma_pagamento, pg.status AS status_pagamento
    FROM pedido p
    LEFT JOIN entrega e ON e.id_pedido = p.id_pedido
    LEFT JOIN pagamento pg ON pg.id_pedido = p.id_pedido
    WHERE p.id_pedido = ? AND p.id_usuario = ?
');
$stmt->bind_param('ii', $idPedido, $_SESSION['id']);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    header('Location: ../Index/Index.php');
    exit;
}

$stmtItens = $conn->prepare('
    SELECT ip.quantidade, ip.preco_unitario, pr.nome, pr.imagem
    FROM item_pedido ip
    JOIN produto pr ON pr.id_produto = ip.id_produto
    WHERE ip.id_pedido = ?
');
$stmtItens->bind_param('i', $idPedido);
$stmtItens->execute();
$itens = $stmtItens->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtItens->close();

function formatarPreco($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

$formasPagamentoLabel = [
    'cartao_credito' => 'Cartão de crédito',
    'cartao_debito'  => 'Cartão de débito',
    'pix'            => 'PIX',
    'boleto'         => 'Boleto',
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - Titan Sports</title>
    <link rel="stylesheet" href="checkout.css">
</head>
<body>

<?php require_once('../NavBar/Navbar.php'); ?>

<div class="checkout-progress">
    <div class="progress-step done"><span class="step-circle"><i class="fas fa-check"></i></span><span class="step-label">Carrinho</span></div>
    <div class="progress-line done"></div>
    <div class="progress-step done"><span class="step-circle"><i class="fas fa-check"></i></span><span class="step-label">Entrega e Pagamento</span></div>
    <div class="progress-line done"></div>
    <div class="progress-step active"><span class="step-circle">3</span><span class="step-label">Confirmação</span></div>
</div>

<main class="confirmacao-container">
    <div class="card-checkout confirmacao-card">
        <i class="fas fa-check-circle confirmacao-icone"></i>
        <h1>Pedido realizado com sucesso!</h1>
        <p>Número do pedido: <strong>#<?php echo str_pad((string) $pedido['id_pedido'], 6, '0', STR_PAD_LEFT); ?></strong></p>
        <p>Status: <strong><?php echo htmlspecialchars($pedido['status_pedido']); ?></strong></p>

        <div class="confirmacao-itens">
            <?php foreach ($itens as $item): ?>
                <div class="resumo-item">
                    <img src="../ImagensProdutos/<?php echo htmlspecialchars($item['imagem']); ?>"
                         alt="<?php echo htmlspecialchars($item['nome']); ?>">
                    <div class="resumo-item-info">
                        <span class="resumo-item-nome"><?php echo htmlspecialchars($item['nome']); ?></span>
                        <span class="resumo-item-qtd">Qtd: <?php echo intval($item['quantidade']); ?></span>
                    </div>
                    <span class="resumo-item-preco"><?php echo formatarPreco($item['preco_unitario'] * $item['quantidade']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="resumo-totais">
            <div class="linha-total"><span>Frete</span><span><?php echo formatarPreco($pedido['frete']); ?></span></div>
            <div class="linha-total total-final"><span>Total pago</span><span><?php echo formatarPreco($pedido['valor_total']); ?></span></div>
        </div>

        <div class="confirmacao-entrega">
            <h3>Entrega</h3>
            <p><?php echo htmlspecialchars($pedido['endereco'] . ' - ' . $pedido['cidade'] . '/' . $pedido['estado'] . ' - CEP ' . $pedido['cep']); ?></p>
            <p>Status: <?php echo htmlspecialchars($pedido['status_entrega']); ?></p>
        </div>

        <div class="confirmacao-pagamento">
            <h3>Pagamento</h3>
            <p>
                <?php echo htmlspecialchars($formasPagamentoLabel[$pedido['forma_pagamento']] ?? $pedido['forma_pagamento']); ?>
                — <?php echo htmlspecialchars($pedido['status_pagamento']); ?>
            </p>
        </div>

        <a href="../Perfil/meus_pedidos.php" class="btn-finalizar btn-ver-pedidos">Ver meus pedidos</a>
        <a href="../Index/Index.php" class="link-continuar-comprando">Continuar comprando</a>
    </div>
</main>

</body>
</html>
