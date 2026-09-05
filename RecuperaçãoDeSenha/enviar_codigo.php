<?php

require_once __DIR__ . '/../session_config.php';
titan_start_session();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

require '../connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $mensagem = "Informe o e-mail cadastrado.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "E-mail inválido.";
    } else {

        // Busca o usuário pelo e-mail
        $sql = "SELECT id_usuario, nome, email
                FROM usuario
                WHERE email = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 0) {
            $mensagem = "Nenhuma conta encontrada com este e-mail.";
        } else {

            $usuario = $resultado->fetch_assoc();
            $idUsuario = $usuario['id_usuario'];
            $nomeUsuario = $usuario['nome'];

            // Gera um código de 6 números
            $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $codigoHash = password_hash($codigo, PASSWORD_DEFAULT);

            // Validade de 10 minutos
            $expiraEm = date('Y-m-d H:i:s', time() + 600);

            // Armazena o código no banco
            $sql = "INSERT INTO recuperacao_senha
                        (id_usuario, codigo_hash, expira_em)
                    VALUES (?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $idUsuario, $codigoHash, $expiraEm);
            $stmt->execute();

            // Guarda as informações na sessão para os próximos passos
            $_SESSION['id_usuario_recuperacao'] = $idUsuario;
            $_SESSION['email_recuperacao'] = $email;

            // Envia o código por e-mail
            try {

                $mail = new PHPMailer(true);

                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['MAIL_USERNAME'];
                $mail->Password = $_ENV['MAIL_PASSWORD'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom($_ENV['MAIL_USERNAME'], 'Titan Sports');
                $mail->addAddress($email, $nomeUsuario);

                $mail->isHTML(true);
                $mail->Subject = 'Código de recuperação de senha - Titan Sports';

                $mail->Body = '
                    <h2>Recuperação de senha</h2>
                    <p>Olá, ' . htmlspecialchars($nomeUsuario) . '!</p>
                    <p>Recebemos uma solicitação para redefinir sua senha.</p>
                    <p>Use o código abaixo para continuar:</p>
                    <p style="font-size: 28px; font-weight: bold; letter-spacing: 4px;">' . $codigo . '</p>
                    <p>Este código é válido por 10 minutos.</p>
                    <p>Se você não solicitou, ignore este e-mail.</p>
                ';

                $mail->AltBody = "Seu código de recuperação é: $codigo (válido por 10 minutos).";

                $mail->send();

                // Redireciona para a verificação do código
                header("Location: verificar_codigo.php");
                exit;

            } catch (MailException $e) {

                $mensagem = "Não foi possível enviar o e-mail. Tente novamente: " . $mail->ErrorInfo;
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
    <title>Recuperar senha - Titan Sports</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <h1>Recuperar senha</h1>

    <?php if (!empty($mensagem)): ?>
        <p class="mensagem">
            <?= htmlspecialchars($mensagem) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label for="email">E-mail</label>
        <input
            type="email"
            id="email"
            name="email"
            placeholder="Digite seu e-mail"
            required
        >

        <button type="submit">Enviar código</button>

    </form>

    <a href="../login.php">Voltar para o login</a>

</body>

</html>
