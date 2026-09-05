<?php
require_once __DIR__ . '/../session_config.php';
titan_start_session();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não exibir na tela, mas registrar

// Proteção: redireciona se não estiver logado
if (!isset($_SESSION['id'])) {
    header("Location: ../Login/Login.php");
    exit();
}

// Verificar se foi enviado um arquivo
if (!isset($_FILES['avatar'])) {
    header("Location: perfil.php?erro=nenhum_arquivo");
    exit();
}

// Verificar erros do upload
$upload_error = $_FILES['avatar']['error'];
if ($upload_error !== UPLOAD_ERR_OK) {
    $erros_upload = [
        UPLOAD_ERR_INI_SIZE => 'arquivo_grande',
        UPLOAD_ERR_FORM_SIZE => 'arquivo_grande',
        UPLOAD_ERR_PARTIAL => 'falha_upload',
        UPLOAD_ERR_NO_FILE => 'nenhum_arquivo',
        UPLOAD_ERR_NO_TMP_DIR => 'falha_upload',
        UPLOAD_ERR_CANT_WRITE => 'falha_upload',
        UPLOAD_ERR_EXTENSION => 'extensao_invalida'
    ];
    $erro = $erros_upload[$upload_error] ?? 'falha_upload';
    header("Location: perfil.php?erro=$erro");
    exit();
}

$arquivo = $_FILES['avatar'];
$usuario_id = $_SESSION['id'];

// Validações básicas do arquivo
if (empty($arquivo['name'])) {
    header("Location: perfil.php?erro=nenhum_arquivo");
    exit();
}

if (!is_uploaded_file($arquivo['tmp_name'])) {
    header("Location: perfil.php?erro=falha_upload");
    exit();
}

// Diretório para salvar uploads
$upload_dir = __DIR__ . '/uploads/avatares/';

// Criar diretório se não existir
if (!is_dir($upload_dir)) {
    if (!@mkdir($upload_dir, 0777, true)) {
        header("Location: perfil.php?erro=pasta_criacao");
        exit();
    }
    @chmod($upload_dir, 0777);
}

// Validações
$max_size = 5 * 1024 * 1024; // 5MB
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Verificar tamanho
if ($arquivo['size'] > $max_size) {
    header("Location: perfil.php?erro=arquivo_grande");
    exit();
}

// Verificar tipo MIME
$finfo = @finfo_open(FILEINFO_MIME_TYPE);
$mime_type = $finfo ? finfo_file($finfo, $arquivo['tmp_name']) : $arquivo['type'];
if ($finfo) finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    header("Location: perfil.php?erro=tipo_invalido");
    exit();
}

// Verificar extensão
$pathinfo = pathinfo($arquivo['name']);
$ext = strtolower($pathinfo['extension']);
if (!in_array($ext, $allowed_ext)) {
    header("Location: perfil.php?erro=extensao_invalida");
    exit();
}

// Gerar nome único para o arquivo
$nome_arquivo = 'avatar_' . $usuario_id . '_' . time() . '.' . $ext;
$caminho_completo = $upload_dir . $nome_arquivo;

// Mover arquivo para o diretório de uploads
if (!move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
    header("Location: perfil.php?erro=falha_upload");
    exit();
}

// Garantir permissões corretas
@chmod($caminho_completo, 0644);

// Caminho relativo para salvar no banco de dados
// Relativo ao arquivo perfil.php que está em /Perfil/
$caminho_relativo = 'uploads/avatares/' . $nome_arquivo;

// Atualizar no banco de dados
require_once '../connection.php';

$sql = "UPDATE usuario SET foto_perfil = ? WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    @unlink($caminho_completo);
    header("Location: perfil.php?erro=falha_banco");
    exit();
}

$stmt->bind_param("si", $caminho_relativo, $usuario_id);

if ($stmt->execute()) {
    $_SESSION['avatar'] = $caminho_relativo;
    $stmt->close();
    header("Location: perfil.php?sucesso=avatar_atualizado");
} else {
    @unlink($caminho_completo);
    $stmt->close();
    header("Location: perfil.php?erro=falha_banco");
}
exit();
?>
