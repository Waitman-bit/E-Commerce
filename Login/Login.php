<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

    include("../connection.php");

    if(isset($_POST['registrar'])){

    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $tel = $_POST['telefone'];
    $senha = $_POST['senha'];

    $sql = "INSERT INTO usuario(nome, email, telefone, senha)
            VALUES ('$nome', '$email', '$tel', '$senha')";

    if($conn->query($sql) === TRUE){
        echo "Usuário cadastrado!";
    } else {
        echo "Erro: " . $conn->error;
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="Login.css">
</head>
<body>
     <div class="container" id="container">
        <div class="form-container sign-up">
            <!-- Criar Conta -->
            <form method="POST">
                <h1 class="title">Criar Conta</h1> <br>
                <span>Informe suas credenciais para continuar</span>
                <input type="text" name="nome" placeholder="Nome">
                <input type="email" name="email" placeholder="Email">
                <input type="tel" name="telefone" pattern="\(\d{2}\)\s\d{5}-\d{4}" placeholder="(16) 99999-9999">
                <input type="text" placeholder="CEP" maxlength="9" pattern="\d{5}-\d{3}" name="cep" placeholder="CEP">
                <input type="text" placeholder="CPF" maxlength="14" pattern="\d{3}\.\d{3}\.\d{3}-\d{2}" name="cpf" placeholder="CPF">
                <input type="password" name="senha" placeholder="Senha">
                <button name="registrar">Registrar</button>
            </form>
        </div>
        <div class="form-container sign-in">
        <!-- Login -->
            <form method="POST">
                <h1 class="title">Fazer Login</h1>
                <span>Preencha com suas informações!</span>
                <input type="email" placeholder="Email">
                <input type="password" placeholder="Senha">
                <a href="../NovaSenha/NovaSenha.php">Esqueceu a Senha?</a>
                <button>Fazer Login</button>
            </form>
        </div>
         <!-- Painéis -->
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Voltar Para o Login!</h1>
                    <br>
                    <button class="hidden" id="login">Voltar</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h2>Não Tem Conta?</h2>
                    <h1>Realizar Cadastro!</h1> <br>
                    <button class="hidden" id="register">Seguir</button>
                </div>
            </div>
        </div>
    </div>
    <script src="Login.js"></script>
</body>
</html>
