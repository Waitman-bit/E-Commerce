<?php
/**
 * calcular_frete.php
 *
 * Endpoint AJAX chamado pelo checkout.js sempre que o usuário informa o CEP
 * ou troca o tipo de entrega. Retorna JSON com valor, prazo e região.
 *
 * Parâmetros GET:
 *   cep  -> CEP do destinatário (com ou sem máscara)
 *   tipo -> 'padrao' | 'expressa' | 'retirada'
 */

require_once('frete.php');

header('Content-Type: application/json; charset=utf-8');

$cep  = $_GET['cep'] ?? '';
$tipo = $_GET['tipo'] ?? 'padrao';

echo json_encode(titan_calcularFrete($cep, $tipo));
