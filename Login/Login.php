<!DOCTYPE html>
<?php
    session_start(); // <- adicione no TOPO do arquivo, antes do HTML
?>
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
                <input type="tel" maxlength="15" name="telefone" pattern="\(\d{2}\)\s\d{5}-\d{4}" placeholder="(16) 99999-9999" id="telefone">
                <input type="text" name="cep" placeholder="CEP" maxlength="9" pattern="\d{5}-\d{3}" name="cep" placeholder="CEP">
                <input type="text" name="cpf" placeholder="CPF" maxlength="14" pattern="\d{3}\.\d{3}\.\d{3}-\d{2}" placeholder="CPF">
                <input type="password" name="senha" placeholder="Senha">
                <button name="registrar">Registrar</button>
            </form>
        </div>
        <div class="form-container sign-in">
            <!-- Login -->
            <form method="POST">
                <h1 class="title">Fazer Login</h1>
                <span>Preencha com suas informações!</span>
                <input type="email" name="email" placeholder="Email">
                <input type="password" name="senha" placeholder="Senha">
                <a href="../NovaSenha/NovaSenha.php">Esqueceu a Senha?</a>
                <button name="fazerlogin">Fazer Login</button>
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

    <?php

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    include("../connection.php");

    if (isset($_POST['registrar'])) {

        // Recebe os dados
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $tel = trim($_POST['telefone']);
        $senha = trim($_POST['senha']);
        $cep = trim($_POST['cep']);
        $cpf = trim($_POST['cpf']);

        // Verifica campos vazios
        if (empty($nome) ||
            empty($email) ||
            empty($tel) ||
            empty($senha) ||
            empty($cep) ||
            empty($cpf)) 
            {
            echo "<span style='color:white;'>Preencha todos os campos!</span>";
            }
            else 
            {
                // HASH DA SENHA
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        
                // Verifica se usuário já existe
                $verificar = $conn->prepare(
                    "SELECT id_usuario FROM usuario WHERE email = ? OR cpf = ?");

                $verificar->bind_param("ss", $email, $cpf);

                $verificar->execute();

                $resultado = $verificar->get_result();

                if ($resultado->num_rows > 0) {

                    echo "<span style='color:white;'>Usuário já existe!</span>";

                }
                else 
                {

                    // INSERT SEGURO
                    $stmt = $conn->prepare(
                        "INSERT INTO usuario
                    (nome, email, telefone, senha, cep, cpf)
                    VALUES (?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->bind_param(
                        "ssssss",
                        $nome,
                        $email,
                        $tel,
                        $senhaHash,
                        $cep,
                        $cpf
                    );

                    if ($stmt->execute()) {

                        header("Location: ../Index/Index.php");
                        exit();

                    } 
                    else 
                    {

                        echo "<span style='color:white;'>Cadastro Inválido</span>";

                    }
                }
            }
    }
    if(isset($_POST['fazerlogin'])){

    // Recebe os dados
        $email = trim($_POST['email']);
        $senha = trim($_POST['senha']);

        // Verifica campos vazios
        if(empty($email) || empty($senha)){

            echo "<span style='color:white;'>Preencha todos os campos!</span>";

        } else {

            // Procura usuário pelo email
            $stmt = $conn->prepare(
                "SELECT * FROM usuario WHERE email = ?"
            );

            $stmt->bind_param("s", $email);

            $stmt->execute();

            $resultado = $stmt->get_result();

            // Verifica se encontrou usuário
            if($resultado->num_rows > 0){

                // Pega dados do usuário
                $usuario = $resultado->fetch_assoc();

                // Verifica senha
                if(password_verify($senha, $usuario['senha'])){

                    // Cria a sessão com os dados do usuário
                    $_SESSION['id']    = $usuario['id_usuario'];
                    $_SESSION['nome']  = $usuario['nome'];
                    $_SESSION['email'] = $usuario['email'];

                    header("Location: ../Index/Index.php");
                    exit();
                    // Aqui depois você pode criar sessão

                } else {

                    echo "<span style='color:white;'>Senha incorreta!</span>";

                }

            } else {

                echo "<span style='color:white;'>Usuário não encontrado!</span>";

            }
        }
    }

    ?>
</body>

</html>