<?php
function titan_marcar_pedido_pago(mysqli $conn, int $idPedido, float $valorPago): void
{
    $stmt = $conn->prepare('UPDATE contas_receber SET data_pagamento = NOW(), valor_pago = ?
                            WHERE id_pedido = ? AND data_pagamento IS NULL');
    $stmt->bind_param('di', $valorPago, $idPedido);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE pedido SET status_pedido = 'Pago'
                            WHERE id_pedido = ? AND status_pedido = 'Aguardando pagamento'");
    $stmt->bind_param('i', $idPedido);
    $stmt->execute();
    $stmt->close();
}