<?php ?>

<link rel="stylesheet" href="/E-Commerce/NavBar/NavBar.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<nav class="navbar-ml">

  <div class="container-navbar">

    <a href="#" class="logo">
      <img src="/E-Commerce/NavBar/Logo-sem-fundo.png" alt="Logo">
    </a>

    <form class="search-box">
      <input type="search" placeholder="Buscar produtos, marcas e muito mais...">
      <button type="submit">
        <i class="fas fa-search"></i>
      </button>
    </form>

    <div class="user-area">
      <a href="#" class="profile-box">
        <img src="/E-Commerce/NavBar/Perfil.png" class="profile-img" alt="Perfil">
        <div class="profile-info">
          <span class="welcome">Entrar</span>
          <span class="status">Minha conta</span>
        </div>
      </a>
    </div>
      <img src="/E-Commerce/NavBar/carrinho.png" alt="Carrinho" class="cart-icon">
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