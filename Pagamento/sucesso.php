<?php
require_once __DIR__ . '/../connection.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../Login/Login.php');
    exit;
}

$idPedido = (int) ($_GET['pedido'] ?? 0);
$idUsuario = (int) $_SESSION['id'];

$stmt = $conn->prepare("SELECT id_pedido, valor_total FROM pedido
                        WHERE id_pedido = ? AND id_usuario = ? AND status_pedido = 'Pago'");
$stmt->bind_param('ii', $idPedido, $idUsuario);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    header('Location: ../Carrinho/carrinho.php');
    exit;
}

// Pagou: limpa o carrinho e os dados do checkout
unset($_SESSION['carrinho'], $_SESSION['checkout'], $_SESSION['pedido_em_andamento']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <link rel="icon" href="../logoicon.ico" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido confirmado - TitanSports</title>
    <link rel="stylesheet" href="../Checkout/checkout.css">
</head>
<body>
<div class="etapas-checkout">
    <span class="etapa">1. Checkout</span>
    <span class="etapa">2. Pagamento</span>
    <span class="etapa ativa">3. Confirmação</span>
</div>
<section class="card-checkout" style="max-width:480px;margin:0 auto;text-align:center;">
    <h2>Pagamento aprovado!</h2>
    <p>Pedido #<?php echo (int) $pedido['id_pedido']; ?> confirmado.</p>
    <p>Total pago: R$ <?php echo number_format((float) $pedido['valor_total'], 2, ',', '.'); ?></p>
</section>
<div class="container-voltar-loja">
    <button class="btn-voltar-loja" onclick="window.location.href='../Index/Index.php'">Voltar para a loja</button>
</div>
</body>
</html>