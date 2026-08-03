<?php
require_once("../connection.php");

$pesquisa = $_GET['pesquisa'] ?? '';

if (!empty($pesquisa)) {

    $busca = "%{$pesquisa}%";

    $stmt = mysqli_prepare($conn, "
        SELECT p.*
        FROM produto p
        INNER JOIN categoria c
            ON p.id_categoria = c.id_categoria
        WHERE
            p.nome LIKE ?
            OR p.marca LIKE ?
            OR p.descricao LIKE ?
            OR c.nome LIKE ?
    ");

    mysqli_stmt_bind_param($stmt, "ssss", $busca, $busca, $busca, $busca);
    mysqli_stmt_execute($stmt);

    $pesquisaResultado = mysqli_stmt_get_result($stmt);

} else {

    $destaques = mysqli_query($conn, "SELECT * FROM produto ORDER BY RAND() LIMIT 4");

    $todos = mysqli_query($conn, "SELECT * FROM produto ORDER BY nome ASC");

}
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
  <a class="cat-chip" href="produtos.php?categoria=corrida">Corrida</a>
  <a class="cat-chip" href="produtos.php?categoria=basquete">Basquete</a>
   <a class="cat-chip" href="produtos.php?categoria=futebol">Futebol</a>
   <a class="cat-chip" href="produtos.php?categoria=natacao">Natação</a>
   <a class="cat-chip" href="produtos.php?categoria=artes-marciais">Artes Marciais</a>
   <a class="cat-chip" href="produtos.php?categoria=musculacao">Musculação</a>
  <a class="cat-chip" href="produtos.php?categoria=suplementos">Suplementos</a>
  <a class="cat-chip" href="produtos.php?categoria=roupas">Vestuário</a>
  <a class="cat-chip" href="produtos.php?categoria=todos">Acessórios</a>
</div>

<!-- Produtos -->

<?php if (!empty($pesquisa)) { ?>

<section class="produtos-section">
    <h2 class="produtos-titulo">
        Resultados para "<?php echo htmlspecialchars($pesquisa); ?>"
    </h2>

    <div class="cards-grid">
        <?php while($produto = mysqli_fetch_assoc($pesquisaResultado)){ ?>
        <div class="product-card">
            <div class="card-img">
                <img src="../ImagensProdutos/<?php echo $produto['imagem']; ?>" alt="<?php echo $produto['nome']; ?>">
            </div>

            <div class="card-body">
                <p class="card-name">
                    <?php echo $produto['nome']; ?>
                </p>

                <div class="card-footer">
                    <span class="card-price">
                        R$ <?php echo number_format($produto['preco'],2,",","."); ?>
                    </span>

                    <a href="../Produto/produto.php?id=<?php echo $produto['id_produto']; ?>" class="btn-add">
                        + Ver produto
                    </a>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
</section>

<?php } else { ?>

<section class="produtos-section">
    <h2 class="produtos-titulo">Produtos em Destaque</h2>

    <div class="cards-grid">
        <?php while($produto = mysqli_fetch_assoc($destaques)){ ?>
        <div class="product-card">
            <div class="card-img">
                <img src="../ImagensProdutos/<?php echo $produto['imagem']; ?>" alt="<?php echo $produto['nome']; ?>">
            </div>

            <div class="card-body">
                <p class="card-name">
                    <?php echo $produto['nome']; ?>
                </p>

                <div class="card-footer">
                    <span class="card-price">
                        R$ <?php echo number_format($produto['preco'],2,",","."); ?>
                    </span>

                    <a href="../Produto/produto.php?id=<?php echo $produto['id_produto']; ?>" class="btn-add">
                        + Ver produto
                    </a>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
</section>
<?php } ?>
      
<footer>
  <p>&copy; 2026 TITAN SPORTS. Todos os direitos reservados.</p>
</footer>
<script src="Index.js"></script>
</body>
</html>