<?php
function cpfKey(string $nome): string {
    $config = require __DIR__ . '/config_chaves.php';
    if (empty($config[$nome])) {
        throw new RuntimeException("Chave $nome não configurada.");
    }
    return base64_decode($config[$nome]);
}

function cpfCriptografar(string $cpf): string {
    $cpf = preg_replace('/\D/', '', $cpf);
    $key = cpfKey('CPF_ENC_KEY');
    $iv  = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($cpf, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $cipher);
}

function cpfDescriptografar(string $dados): ?string {
    $key = cpfKey('CPF_ENC_KEY');
    $raw = base64_decode($dados, true);
    if ($raw === false || strlen($raw) < 29) return null;
    $iv     = substr($raw, 0, 12);
    $tag    = substr($raw, 12, 16);
    $cipher = substr($raw, 28);
    $cpf = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return $cpf === false ? null : $cpf;
}

function cpfHash(string $cpf): string {
    $cpf = preg_replace('/\D/', '', $cpf);
    return hash_hmac('sha256', $cpf, cpfKey('CPF_HASH_KEY'));
}

// 12345678909 -> 123.456.789-09
function cpfFormatar(string $cpf): string {
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) !== 11) return $cpf;
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

// 12345678909 -> ***.456.789-**
function cpfMascarar(string $cpf): string {
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) !== 11) return '';
    return '***.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-**';
}