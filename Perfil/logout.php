<?php
session_start();

// Limpa todas as variáveis da sessão
$_SESSION = [];

// Destrói a sessão
session_destroy();

// Volta para a página inicial
header("Location: ../Index/Index.php");
exit();
?>