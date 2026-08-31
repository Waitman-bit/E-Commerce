<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../connection.php';

$mensagem = '';

if (!isset($_SESSION['id_usuario_recuperacao'])) {
    die("Solicitação de recuperação inválida.");
}

$idUsuario = $_SESSION['id_usuario_recuperacao'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $codigo = trim($_POST['codigo'] ?? '');

    if (empty($codigo)) {
        $mensagem = "Digite o código recebido por e-mail.";
    } elseif (!preg_match('/^\d{6}$/', $codigo)) {
        $mensagem = "O código deve conter 6 números.";
    } else {

        // Busca o código mais recente ainda não utilizado
        $sql = "SELECT id, codigo_hash, expira_em
                FROM recuperacao_senha
                WHERE id_usuario = ?
                  AND usado = 0
                ORDER BY criado_em DESC
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 0) {

            $mensagem = "Código inválido ou já utilizado.";

        } else {

            $recuperacao = $resultado->fetch_assoc();

            // Verifica se o código expirou
            if (strtotime($recuperacao['expira_em']) < time()) {

                $mensagem = "Esse código expirou. Solicite um novo código.";

            } elseif (!password_verify($codigo, $recuperacao['codigo_hash'])) {

                $mensagem = "Código incorreto.";

            } else {

                // Código correto
                $_SESSION['id_recuperacao'] = $recuperacao['id'];

                header("Location: nova_senha.php
");
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Verificar código - Titan Sports</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <h1>Verificação</h1>

    <p>
        Digite o código de 6 números enviado para seu e-mail.
    </p>

    <?php if (!empty($mensagem)): ?>
        <p class="mensagem">
            <?= htmlspecialchars($mensagem) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label for="codigo">
            Código de recuperação
        </label>

        <input
            type="text"
            id="codigo"
            name="codigo"
            maxlength="6"
            inputmode="numeric"
            pattern="[0-9]{6}"
            placeholder="000000"
            required
        >

        <button type="submit">
            Verificar código
        </button>

    </form>

</body>

</html>
