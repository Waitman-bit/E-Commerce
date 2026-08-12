<?php
/**
 * buscar_cep.php
 *
 * Endpoint AJAX chamado pelo checkout.js quando o usuário digita um CEP.
 * Devolve o endereço (via ViaCEP) e as opções de frete já calculadas
 * no servidor, para preencher o formulário de checkout dinamicamente.
 *
 * NÃO decide nada sozinho: o valor final do frete é sempre recalculado
 * de novo, no servidor, quando o pedido é confirmado em pagamento.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/frete.php';

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'erro' => 'Você precisa estar logado.']);
    exit;
}

$cepBruto = $_GET['cep'] ?? '';
$cep = preg_replace('/\D/', '', $cepBruto);

if (strlen($cep) !== 8) {
    echo json_encode(['sucesso' => false, 'erro' => 'CEP inválido. Digite os 8 números do CEP.']);
    exit;
}

$endereco = titan_buscar_cep($cep);

if ($endereco === false || empty($endereco['uf'])) {
    echo json_encode([
        'sucesso' => false,
        'erro'    => 'Não foi possível localizar esse CEP automaticamente. Preencha o endereço manualmente.',
    ]);
    exit;
}

$opcoesFrete = titan_opcoes_frete($endereco['uf']);

echo json_encode([
    'sucesso'    => true,
    'logradouro' => $endereco['logradouro'],
    'bairro'     => $endereco['bairro'],
    'cidade'     => $endereco['cidade'],
    'estado'     => $endereco['uf'],
    'fretes'     => $opcoesFrete,
]);
