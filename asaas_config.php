<?php
$asaasApiKey = $_ENV['ASAAS_API_KEY'] ?? getenv('ASAAS_API_KEY');

if (!$asaasApiKey && is_file(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
        if (str_starts_with(ltrim($linha), '#') || !str_contains($linha, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $linha, 2);
        $_ENV[trim($k)] = trim($v, " \t\"'");
    }
    $asaasApiKey = $_ENV['ASAAS_API_KEY'] ?? null;
}

if (!$asaasApiKey) {
    http_response_code(500);
    exit('ASAAS_API_KEY não configurado no .env');
}

define('ASAAS_API_KEY', $asaasApiKey);
define('ASAAS_BASE_URL', 'https://api-sandbox.asaas.com/v3'); // troca pra api.asaas.com/v3 quando for produção

/**
 * Faz uma chamada à API do Asaas e devolve [statusHttp, corpoDecodificado].
 */
function titan_asaas_request(string $method, string $path, ?array $body = null): array
{
    $ch = curl_init(ASAAS_BASE_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'access_token: ' . ASAAS_API_KEY,
            'Content-Type: application/json',
            'User-Agent: TitanSports-TCC',
        ],
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $resposta = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$status, json_decode($resposta, true)];
}