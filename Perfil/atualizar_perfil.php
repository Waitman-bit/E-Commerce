<?php
require_once __DIR__ . '/../session_config.php';
titan_start_session();

if (!isset($_SESSION['id'])) {
    header('Location: ../Login/Login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil.php');
    exit();
}

$csrf = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_editar_perfil']) || !is_string($csrf)
    || !hash_equals($_SESSION['csrf_editar_perfil'], $csrf)) {
    header('Location: perfil.php?erro=csrf');
    exit();
}

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['tel'] ?? '');
$cep = trim($_POST['cep'] ?? '');

if ($nome === '' || mb_strlen($nome) > 40
    || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100
    || mb_strlen($telefone) > 20 || mb_strlen($cep) > 9) {
    header('Location: perfil.php?erro=dados_invalidos');
    exit();
}

require_once __DIR__ . '/../connection.php';
$idUsuario = (int) $_SESSION['id'];

$stmt = $conn->prepare('SELECT id_usuario FROM usuario WHERE email = ? AND id_usuario <> ? LIMIT 1');
if (!$stmt) {
    header('Location: perfil.php?erro=falha_atualizar');
    exit();
}
$stmt->bind_param('si', $email, $idUsuario);
$stmt->execute();
$emailEmUso = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($emailEmUso) {
    header('Location: perfil.php?erro=email_em_uso');
    exit();
}

$stmt = $conn->prepare('UPDATE usuario SET nome = ?, email = ?, telefone = ?, cep = ? WHERE id_usuario = ?');
if (!$stmt) {
    header('Location: perfil.php?erro=falha_atualizar');
    exit();
}
$stmt->bind_param('ssssi', $nome, $email, $telefone, $cep, $idUsuario);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    header('Location: perfil.php?erro=falha_atualizar');
    exit();
}

$_SESSION['nome'] = $nome;
$_SESSION['email'] = $email;
$_SESSION['tel'] = $telefone;
$_SESSION['cep'] = $cep;

header('Location: perfil.php?sucesso=perfil_atualizado');
exit();
