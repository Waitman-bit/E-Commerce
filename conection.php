<?php

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "tcc";

$conection = new mysqli($host, $usuario, $senha, $banco);

if ($conection->connect_error) {
    die("Erro na conexão: " . $conection->connect_error);
}

?>