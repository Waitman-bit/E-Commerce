<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../connection.php';

$mensagem = '';

if (!isset($_SESSION['id_recuperacao'])) {
    die("Acesso inválido. Solicite uma nova recuperação de senha.");
}

$idRecuperacao = $_SESSION['id_recuperacao'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    // Verifica se os campos foram preenchidos
    if (empty($novaSenha) || empty($confirmarSenha)) {

        $mensagem = "Preencha os dois campos.";

    // Verifica se as senhas são iguais
    } elseif ($novaSenha !== $confirmarSenha) {

        $mensagem = "As senhas não coincidem.";

    // Verifica tamanho mínimo
    } elseif (strlen($novaSenha) < 8) {

        $mensagem = "A senha deve possuir pelo menos 8 caracteres.";

    } else {

        // Busca o usuário relacionado à recuperação
        $sql = "SELECT id_usuario
                FROM recuperacao_senha
                WHERE id = ?
                  AND usado = 0
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idRecuperacao);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 0) {

            $mensagem = "Solicitação de recuperação inválida.";

        } else {

            $recuperacao = $resultado->fetch_assoc();

            $idUsuario = $recuperacao['id_usuario'];

            // Cria o hash da nova senha
            $senhaHash = password_hash(
                $novaSenha,
                PASSWORD_DEFAULT
            );

            // Atualiza a senha do usuário
            $sql = "UPDATE usuario
                    SET senha = ?
                    WHERE id_usuario = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $senhaHash, $idUsuario);

            if ($stmt->execute()) {

                // Marca o código como utilizado
                $sql = "UPDATE recuperacao_senha
                        SET usado = 1
                        WHERE id = ?";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $idRecuperacao);
                $stmt->execute();

                // Limpa as informações da recuperação
                unset($_SESSION['id_usuario_recuperacao']);
                unset($_SESSION['email_recuperacao']);
                unset($_SESSION['id_recuperacao']);

                // Redireciona para o login
                header("Location: ../Login/Login.php?senha_alterada=1");
                exit;

            } else {

                $mensagem = "Não foi possível alterar a senha.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Nova senha - Titan Sports</title>
    <link rel="stylesheet" href="style.css">

</head>

<body>

    <h1>Crie uma nova senha</h1>

    <p>
        Digite sua nova senha abaixo.
    </p>

    <?php if (!empty($mensagem)): ?>

        <p class="mensagem">
            <?= htmlspecialchars($mensagem) ?>
        </p>

    <?php endif; ?>

    <form method="POST">

        <label for="nova_senha">
            Nova senha
        </label>

        <input
            type="password"
            id="nova_senha"
            name="nova_senha"
            minlength="8"
            required
        >

        <br><br>

        <label for="confirmar_senha">
            Confirme sua nova senha
        </label>

        <input
            type="password"
            id="confirmar_senha"
            name="confirmar_senha"
            minlength="8"
            required
        >

        <br><br>

        <button type="submit">
            Alterar senha
        </button>

    </form>

</body>

</html>
