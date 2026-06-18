<?php
require_once '../connection.php';

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $nova_senha = isset($_POST['nova_senha']) ? $_POST['nova_senha'] : '';
    $confirmar_senha = isset($_POST['confirmar_senha']) ? $_POST['confirmar_senha'] : '';

    // Validações
    if (empty($email) || empty($nova_senha) || empty($confirmar_senha)) {
        $response['message'] = 'Preencha todos os campos.';
        echo json_encode($response);
        exit;
    }

    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'E-mail inválido.';
        echo json_encode($response);
        exit;
    }

    // Verificar se as senhas coincidem
    if ($nova_senha !== $confirmar_senha) {
        $response['message'] = 'As senhas não coincidem.';
        echo json_encode($response);
        exit;
    }

    // Verificar tamanho mínimo da senha
    if (strlen($nova_senha) < 6) {
        $response['message'] = 'A senha deve ter pelo menos 6 caracteres.';
        echo json_encode($response);
        exit;
    }

    // Verificar se o email está cadastrado no banco
    $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $response['message'] = 'E-mail não cadastrado no sistema.';
        echo json_encode($response);
        $stmt->close();
        exit;
    }

    // Email existe, agora atualizar a senha
    // Você pode usar password_hash() para criptografar a senha, se desejar
    $senha_criptografada = password_hash($nova_senha, PASSWORD_DEFAULT);
    
    $stmt_update = $conn->prepare("UPDATE usuario SET senha = ? WHERE email = ?");
    $stmt_update->bind_param("ss", $senha_criptografada, $email);

    if ($stmt_update->execute()) {
        $response['success'] = true;
        $response['message'] = 'Senha alterada com sucesso!';
    } else {
        $response['message'] = 'Erro ao alterar a senha: ' . $stmt_update->error;
    }

    $stmt->close();
    $stmt_update->close();
    echo json_encode($response);
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="NovaSenha.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
  <title>Esqueceu a Senha</title>
</head>
<body>
<div class="container">
  <div class="card-header">
    <div class="lock-icon">
      <i class="ti ti-lock"></i>
    </div>
    <h1>Crie uma nova senha</h1>
    <p class="subtitle">Digite sua nova senha abaixo</p>
  </div>
  <div class="card-body" id="card-body">
    <form onsubmit="handleSubmit(event)">
 
      <div class="field">
        <label>E-mail</label>
        <div class="input-wrap">
          <input type="email" id="fs-email" placeholder="seu@email.com" autocomplete="email" required>
          <i class="ti ti-mail"></i>
        </div>
      </div>
      <div class="field">
        <label>Nova senha</label>
        <div class="input-wrap">
          <input type="password" id="fs-pass" placeholder="Mínimo 6 caracteres" minlength="6" required>
          <i class="ti ti-key"></i>
        </div>
        <div class="strength-bars">
          <span id="b1"></span>
          <span id="b2"></span>
          <span id="b3"></span>
          <span id="b4"></span>
        </div>
        <span class="strength-label" id="fs-slabel"></span>
      </div>
      <div class="field">
        <label>Confirmar senha</label>
        <div class="input-wrap">
          <input type="password" id="fs-confirm" placeholder="Repita a senha" minlength="6" required>
          <i class="ti ti-shield-check"></i>
        </div>
        <span class="strength-label" id="fs-match"></span>
      </div>
      <hr class="divider">
      <button type="submit" class="btn-confirm">Confirmar nova senha</button>
      <div class="footer-link">
        <a href="../Login/Login.php">← Voltar para o login</a>
      </div>
    </form>
  </div>
  <div class="card-success" id="card-success">
    <div class="success-icon">
      <i class="ti ti-check"></i>
    </div>
    <h2>Senha redefinida!</h2>
    <p>Sua senha foi atualizada com sucesso.<br>Você já pode fazer login.</p>
  </div>
</div>
<script src="NovaSenha.js"></script>
</body>
</html>
 