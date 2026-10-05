<?php
require_once __DIR__ . '/../asaas_config.php';

echo '<pre>';
[$s1, $c] = titan_asaas_request('POST', '/payments', [
    'customer'    => 'cus_000009234731',
    'billingType' => 'PIX',
    'value'       => 10.00,
    'dueDate'     => date('Y-m-d'),
    'description' => 'Teste QR',
]);
var_dump($s1, $c['id'] ?? $c);

if (!empty($c['id'])) {
    var_dump(titan_asaas_request('GET', '/payments/' . $c['id'] . '/pixQrCode'));
}