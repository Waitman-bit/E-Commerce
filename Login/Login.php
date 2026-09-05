<?php
require_once __DIR__ . '/../session_config.php';
titan_start_session();

ini_set('display_errors', 1);
error_reporting(E_ALL);

include("../connection.php");

$mensagem = '';

if (isset($_POST['registrar'])) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tel = trim($_POST['telefone'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $cep = trim($_POST['cep'] ?? '');

    if (empty($nome) || empty($email) || empty($tel) || empty($senha) || empty($cep) || empty($cpf)) {
        $mensagem = 'Preencha todos os campos!';
    } else {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $verificar = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = ? OR cpf = ?");
        $verificar->bind_param("ss", $email, $cpf);
        $verificar->execute();
        $resultado = $verificar->get_result();

        if ($resultado->num_rows > 0) {
            $mensagem = 'Usuário já existe!';
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO usuario
                (nome, email, telefone, senha, cep, cpf)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param("ssssss", $nome, $email, $tel, $senhaHash, $cep, $cpf);

            if ($stmt->execute()) {
                session_regenerate_id(true);
                $idUsuario = $conn->insert_id;
                $_SESSION['id'] = $idUsuario;
                $_SESSION['nome'] = $nome;
                $_SESSION['email'] = $email;
                $_SESSION['tipo'] = 'cliente';
                $_SESSION['avatar'] = '';
                header("Location: ../Index/Index.php");
                exit();
            }

            $mensagem = 'Cadastro Inválido';
        }
    }
}

if (isset($_POST['fazerlogin'])) {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (empty($email) || empty($senha)) {
        $mensagem = 'Preencha todos os campos!';
    } else {
        $stmt = $conn->prepare("SELECT * FROM usuario WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $usuario = $resultado->fetch_assoc();

            if (password_verify($senha, $usuario['senha'])) {
                session_regenerate_id(true);
                $_SESSION['id'] = $usuario['id_usuario'];
                $_SESSION['nome'] = $usuario['nome'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['tipo'] = $usuario['tipo'];
                $_SESSION['avatar'] = $usuario['foto_perfil'] ?? '';

                if ($usuario['tipo'] === 'admin') {
                    header("Location: ../Administrador/Adm.php");
                    exit();
                }

                header("Location: ../Index/Index.php");
                exit();
            }

            $mensagem = 'Senha incorreta!';
        } else {
            $mensagem = 'Usuário não encontrado!';
        }
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
                <input type="tel" maxlength="15" name="telefone" placeholder="(16) 99999-9999" id="telefone">
                <input type="text" name="cpf" placeholder="CPF" maxlength="14" pattern="\d{3}\.\d{3}\.\d{3}-\d{2}" placeholder="CPF" id="cpf">
                <input type="text" placeholder="CEP" maxlength="9" pattern="\d{5}-\d{3}" name="cep" placeholder="CEP" id="cep">
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
                <a href="../RecuperaçãoDeSenha/recuperacao.php">Esqueceu a Senha?</a>
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
        <?php if (!empty($mensagem)): ?>
            <span style="color:white;"><?= htmlspecialchars($mensagem) ?></span>
        <?php endif; ?>
	    <script src="Login.js"></script>
	    <script src="https://code.jquery.com/jquery-4.0.0.min.js"></script>
	    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js"></script>
</body>
</html>
