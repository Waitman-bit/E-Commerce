<?php
require_once __DIR__ . '/../asaas_config.php';

echo '<pre>';
// 1. apaga a chave antiga
var_dump(titan_asaas_request('DELETE', '/pix/addressKeys/5750c7a9-c952-4a55-b4ca-c559549027b2'));

// 2. cria uma nova
var_dump(titan_asaas_request('POST', '/pix/addressKeys', ['type' => 'EVP']));