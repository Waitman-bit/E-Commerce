<?php
    
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
 