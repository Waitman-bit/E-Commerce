<?php
require_once __DIR__ . '/../session_config.php';
titan_start_session();

// Limpa todas as variáveis da sessão
$_SESSION = [];

// Destrói a sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'] ?? '',
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}
session_destroy();

// Volta para a página inicial
header("Location: ../Index/Index.php");
exit();
?>
