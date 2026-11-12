<?php
// Status de entrega que o admin pode escolher
const STATUS_PEDIDO_ADMIN = [
    'Aguardando envio',
    'Aguardando retirada',
    'Em transporte',
    'Entregue',
    'Retirado',
    'Cancelada',
];

// Status -> [classe CSS, ícone] usados no perfil e no admin
const STATUS_PEDIDO_VISUAL = [
    'Aguardando pagamento' => ['pendente',   'fa-clock'],
    'Pendente'             => ['pendente',   'fa-clock'],
    'Pago'                 => ['aguardando', 'fa-check'],
    'Aguardando envio'     => ['aguardando', 'fa-box'],
    'Aguardando retirada'  => ['aguardando', 'fa-store'],
    'Em transporte'        => ['transporte', 'fa-truck'],
    'Entregue'             => ['entregue',   'fa-circle-check'],
    'Retirado'             => ['entregue',   'fa-circle-check'],
    'Cancelada'            => ['cancelado',  'fa-circle-xmark'],
];

function statusPedidoVisual(string $status): array
{
    return STATUS_PEDIDO_VISUAL[trim($status)] ?? ['pendente', 'fa-clock'];
}