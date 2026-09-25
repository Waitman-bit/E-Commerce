<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../asaas_config.php';
require_once __DIR__ . '/pagamento_lib.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'unauthorized']);
    exit;
}

$paymentId = $_GET['id'] ?? '';
$idUsuario = (int) $_SESSION['id'];

$stmt = $conn->prepare('SELECT p.id_pedido FROM contas_receber c
                        JOIN pedido p ON p.id_pedido = c.id_pedido
                        WHERE c.asaas_payment_id = ? AND p.id_usuario = ?');
$stmt->bind_param('si', $paymentId, $idUsuario);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo json_encode(['status' => 'not_found']);
    exit;
}

[, $pagamento] = titan_asaas_request('GET', '/payments/' . $paymentId);

$pago = in_array($pagamento['status'] ?? '', ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'], true);
if ($pago) {
    titan_marcar_pedido_pago($conn, (int) $row['id_pedido'], (float) ($pagamento['value'] ?? 0));
}

echo json_encode(['status' => $pago ? 'approved' : 'pending', 'pedido' => (int) $row['id_pedido']]);