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
$cpf = trim($_POST['cpf'] ?? '');

function validarCPFPerfil($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);

    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += (int) $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ((int) $cpf[$c] !== $d) {
            return false;
        }
    }

    return true;
}

if (!validarCPFPerfil($cpf)) {
    header('Location: perfil.php?erro=cpf_invalido');
    exit();
}

require_once __DIR__ . '/../crypto_cpf.php';

$cpfLimpo = preg_replace('/\D/', '', $cpf);
$cpfCriptografado = cpfCriptografar($cpfLimpo);
$cpfHash = cpfHash($cpfLimpo);

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

$stmt = $conn->prepare('SELECT id_usuario FROM usuario WHERE cpf_hash = ? AND id_usuario <> ? LIMIT 1');
if (!$stmt) {
    header('Location: perfil.php?erro=falha_atualizar');
    exit();
}
$stmt->bind_param('si', $cpfHash, $idUsuario);
$stmt->execute();
$cpfEmUso = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($cpfEmUso) {
    header('Location: perfil.php?erro=cpf_em_uso');
    exit();
}

$stmt = $conn->prepare('UPDATE usuario SET nome = ?, email = ?, telefone = ?, cep = ?, cpf = ?, cpf_hash = ? WHERE id_usuario = ?');
if (!$stmt) {
    header('Location: perfil.php?erro=falha_atualizar');
    exit();
}
$stmt->bind_param('ssssssi', $nome, $email, $telefone, $cep, $cpfCriptografado, $cpfHash, $idUsuario);
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
