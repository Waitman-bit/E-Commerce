<?php
include("../connection.php");

$senhaHash = password_hash("4dm1n", PASSWORD_DEFAULT);

$sql = "INSERT INTO usuario
(nome, cpf, email, telefone, senha, tipo, cep)
VALUES
('Administrador',
 '123.456.789-00',
 'admin@gmail.com',
 '(16) 99999-9999',
 '$senhaHash',
 'admin',
 '15900-000')";

if ($conn->query($sql)) {
    echo "Administrador criado com sucesso!";
} else {
    echo $conn->error;
}