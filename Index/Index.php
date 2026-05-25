<?php
    
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tela Inicial</title>
</head>
<body>
    <!-- Links do bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <!-- Links dos css -->
    <?php require_once '../NavBar/Navbar.php'; ?>
     <link rel="stylesheet" href="aaa.css">
     <!-- Código -->
     <section class = "hero">
        <h1>TITAN SPORTS</h1> 
        <p>Os melhores produtos esportivos para você.</p>
     </section>
      <!-- Imagen -->
      <div class="slide" >
        <img src="Slide1.png" alt="Slide 1">
      </div> <br>
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
        <p class="card-category">Calçados</p>
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
        <img src="bola.png" alt="Bola de Futebol">
      </div>
      <div class="card-body">
        <p class="card-category">Bolas</p>
        <p class="card-name">Bola Oficial de Futebol Campo</p>
        <div class="card-footer">
          <div>
            <span class="card-price">R$ 89,90</span>
          </div>
          <a href="produto.php?id=2" class="btn-add">+ Ver produto</a>
        </div>
      </div>
    </div>

    <div class="product-card">
      <div class="card-img">
        <img src="luvas.png" alt="Luvas de Treino">
        <span class="card-badge">-25%</span>
      </div>
      <div class="card-body">
        <p class="card-category">Musculação</p>
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
        <img src="camisas.png" alt="Kit 3 Camisa Dry-Fit">
      </div>
      <div class="card-body">
        <p class="card-category">Vestuário</p>
        <p class="card-name">Kit 3 Camisas Dry-Fit</p>
        <div class="card-footer">
          <div>
            <span class="card-price">R$ 69,90</span>
          </div>
          <a href="produto.php?id=4" class="btn-add">+ Ver produto</a>
        </div>
      </div>
    </div>
  </div>
</section>
</body>
</html>