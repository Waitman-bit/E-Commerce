<?php

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "tcc";

$conn = new mysqli($host, $usuario, $senha, $banco);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

require_once __DIR__ . '/session_config.php';
titan_start_session();

?>
