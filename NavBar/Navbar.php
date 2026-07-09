<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function buildNavbarAvatarUrl($valor) {
    if (empty($valor)) {
        return '';
    }

    if (preg_match('#^https?://#i', $valor)) {
        return $valor;
    }

    $valor = str_replace('\\', '/', $valor);

    if (strpos($valor, '/') === 0) {
        return $valor;
    }

    return '/E-Commerce/Perfil/' . ltrim($valor, '/');
}

$logado = isset($_SESSION['id']);
$nomeUsuario  = $logado ? htmlspecialchars($_SESSION['nome'])  : 'Entrar';
$emailUsuario = $logado ? htmlspecialchars($_SESSION['email']) : 'Minha conta';
$linkPerfil   = $logado ? '../Perfil/perfil.php' : '../Login/Login.php';
$avatarUrl    = '';

if ($logado) {
    require_once __DIR__ . '/../connection.php';
    $stmt = $conn->prepare('SELECT foto_perfil FROM usuario WHERE id_usuario = ?');
    if ($stmt) {
        $stmt->bind_param('i', $_SESSION['id']);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuarioAvatar = $resultado->fetch_assoc();
        $stmt->close();

        $_SESSION['avatar'] = $usuarioAvatar['foto_perfil'] ?? '';
    }
}

if ($logado && !empty($_SESSION['avatar'])) {
    $avatarUrl = buildNavbarAvatarUrl($_SESSION['avatar']);
}

$avatarSrc = !empty($avatarUrl) ? $avatarUrl : '/E-Commerce/NavBar/Perfil.png';
?>

<link rel="stylesheet" href="/E-Commerce/NavBar/NavBar.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<nav class="navbar-ml">

  <div class="container-navbar">

    <a href="../Index/Index.php" class="logo">
      <img src="/E-Commerce/NavBar/Logo-sem-fundo.png" alt="Logo">
    </a>

    <form class="search-box">
      <input type="search" placeholder="Buscar produtos, marcas e muito mais...">
      <button type="submit">
        <i class="fas fa-search"></i>
      </button>
    </form>

    <div class="user-area">
      <a href="<?= $linkPerfil ?>" class="profile-box">
        <img src="<?= $avatarSrc ?>" class="profile-img" alt="Perfil">
        <div class="profile-info">
          <span class="welcome"><?= $nomeUsuario ?></span>
          <span class="status"><?= $emailUsuario ?></span>
        </div>
      </a>
    </div>
    <a href="../Carrinho/carrinho.php"><img src="/E-Commerce/NavBar/carrinho.png" alt="Carrinho" class="cart-icon"></a>
  </div>

  <div class="menu-bar">
    <div class="dropdown">
      <button class="dropdown-btn">
        Categorias ▼
      </button>
      <div class="dropdown-content">
        <a href="#">Futebol</a>
        <a href="#">Basquete</a>
        <a href="#">Corrida</a>
        <a href="#">Academia</a>
        <a href="#">Natação</a>
        <a href="#">Suplementos</a>
        <a href="#">Camisas de Times</a>
        <a href="#">Acessórios</a>
      </div>
    </div>
    <a href="#">Ofertas</a>
    <a href="#">Masculino</a>
    <a href="#">Feminino</a>
    <a href="#">Lançamentos</a>
  </div>

</nav>

</nav>