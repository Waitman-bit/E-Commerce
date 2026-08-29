<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);

$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;

$mail->Username = $_ENV['MAIL_USERNAME'];
$mail->Password = $_ENV['MAIL_PASSWORD'];

$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

$mail->setFrom(
    $_ENV['MAIL_USERNAME'],
    'Titan Sports'
);

$mail->addAddress('davi.veronezi2204@gmail.com');

$mail->Subject = 'Teste SMTP - Titan Sports';

$mail->Body = '
    <h1>Teste funcionando!</h1>
    <p>A Titan Sports conseguiu enviar este e-mail através do Gmail.</p>
';

$mail->send();
echo 'E-mail enviado com sucesso!';