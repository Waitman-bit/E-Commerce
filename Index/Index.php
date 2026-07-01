<?php
    
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tela Inicial</title>
    <!-- Links do bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <!-- Links dos css -->
    <?php require_once '../NavBar/Navbar.php'; ?>
     <link rel="stylesheet" href="Index.css">
     <link rel="shortcut icon" type="image/x-icon" href="../logoicon.ico">  
</head>
<body>
     <section class = "hero">
      <canvas id="particle-canvas"></canvas>
        <h1>TITAN SPORTS</h1> 
        <p>Os melhores produtos esportivos para você.</p>
     </section> <br> <br>
      
    <div class="categorias-bar">
  <a class="cat-chip" href="produtos.php?categoria=todos">Acessórios</a>
  <a class="cat-chip" href="produtos.php?categoria=corrida">Corrida</a>
   <a class="cat-chip" href="produtos.php?categoria=futebol">Futebol</a>
  <a class="cat-chip" href="produtos.php?categoria=suplementos">Suplementos</a>
  <a class="cat-chip" href="produtos.php?categoria=camisas">Camisas de Time</a>
</div>

      <!-- Produtos -->
       <section class="produtos-section">
  <h2 class="produtos-titulo">Produtos em Destaque</h2>
  <div class="cards-grid">

    <div class="product-card">
      <div class="card-img">
        <img src="tenis.png" alt="Tênis Pro Run X3">
        <span class="card-badge">NOVO</span>
      </div>
      <div class="card-body">
        <p class="card-category">Corrida</p>
        <p class="card-name">Tênis de corrida Pro Run X3</p>
        <div class="card-footer">
          <div>
            <span class="card-price">R$ 299,90</span>
            <span class="card-price-old">R$ 399,90</span>
          </div>
          <a href="produto.php?id=1" class="btn-add">+ Ver produto</a>
        </div>
      </div>
    </div>

    <div class="product-card">
      <div class="card-img">
        <img src="creatina.png" alt="Suplementos">
      </div>
      <div class="card-body">
        <p class="card-category">Suplementos</p>
        <p class="card-name">Kit 2 Creatinas Monohidratas</p>
        <div class="card-footer">
          <div>
            <span class="card-price">R$ 140,50</span>
          </div>
          <a href="produto.php?id=2" class="btn-add">+ Ver produto</a>
        </div>
      </div>
    </div>

    <div class="product-card">
      <div class="card-img">
        <img src="luva.png" alt="Luvas de Treino">
        <span class="card-badge">-25%</span>
      </div>
      <div class="card-body">
        <p class="card-category">Futebol</p>
        <p class="card-name">Luvas de Treino Grip Pro</p>
        <div class="card-footer">
          <div>
            <span class="card-price">R$ 59,90</span>
            <span class="card-price-old">R$ 79,90</span>
          </div>
          <a href="produto.php?id=3" class="btn-add">+ Ver produto</a>
        </div>
      </div>
    </div>

    <div class="product-card">
      <div class="card-img">
        <img src="camisadetime.png" alt="Kit 3 Camisa Dry-Fit">
      </div>
      <div class="card-body">
        <p class="card-category">Camisas de Time</p>
        <p class="card-name">Camisa Santos Ediçao Especial - Charlie Brown Jr</p>
        <div class="card-footer">
          <div>
            <span class="card-price">R$ 120,00</span>
          </div>
          <a href="produto.php?id=4" class="btn-add">+ Ver produto</a>
        </div>
      </div>
    </div>
  </div>
</section>

<footer>
  <p>&copy; 2026 TITAN SPORTS. Todos os direitos reservados.</p>
</footer>
<script src="Index.js"></script>
</body>
</html>