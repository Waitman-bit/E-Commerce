<?php
require_once __DIR__ . '/../connection.php';

$pesquisa = trim($_GET['pesquisa'] ?? '');

if ($pesquisa === '') {
    header('Location: ../Index/Index.php');
    exit;
}

$busca = "%{$pesquisa}%";
$stmt = mysqli_prepare($conn, "
    SELECT p.*, c.nome AS categoria_nome
    FROM produto p
    INNER JOIN categoria c
        ON p.id_categoria = c.id_categoria
    WHERE p.nome LIKE ?
       OR p.marca LIKE ?
       OR p.descricao LIKE ?
       OR c.nome LIKE ?
    ORDER BY p.nome ASC
");

mysqli_stmt_bind_param($stmt, 'ssss', $busca, $busca, $busca, $busca);
mysqli_stmt_execute($stmt);
$pesquisaResultado = mysqli_stmt_get_result($stmt);

$titlePesquisa = htmlspecialchars($pesquisa, ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados para "<?= $titlePesquisa ?>"</title>
    <link rel="icon" href="../logoicon.ico" type="image/png">
    <?php require_once __DIR__ . '/../NavBar/Navbar.php'; ?>
    <link rel="stylesheet" href="../PáginaDosProdutos/PaginaDosProdutos.css">
    <link rel="stylesheet" href="Resultado.css">
</head>
<body>
    <main class="produtos-page">
        <section class="hero">
            <h1>Resultados para "<?= $titlePesquisa ?>"</h1>
            <p><?= mysqli_num_rows($pesquisaResultado) ?> produto(s) encontrado(s)</p>
        </section>

        <section class="produtos-section">
            <?php if (mysqli_num_rows($pesquisaResultado) > 0) : ?>
                <div class="cards-grid">
                    <?php while ($produto = mysqli_fetch_assoc($pesquisaResultado)) : ?>
                        <article class="product-card">
                            <div class="card-img">
                                <a href="../Produto/produto.php?id=<?= $produto['id_produto']; ?>">
                                    <img src="../ImagensProdutos/<?= rawurlencode($produto['imagem']); ?>"
                                         alt="<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?>"
                                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 240 220%27%3E%3Crect fill=%27%231a1a1a%27 width=%27240%27 height=%27220%27/%3E%3Ctext x=%2750%25%27 y=%2750%25%27 dominant-baseline=%27middle%27 text-anchor=%27middle%27 fill=%27%23555%27 font-size=%2714%27%3ESem imagem%3C/text%3E%3C/svg%3E'">
                                </a>
                            </div>
                            <div class="card-body">
                                <span class="card-category"><?= htmlspecialchars($produto['categoria_nome']); ?></span>
                                <h3><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="card-description"><?= htmlspecialchars($produto['descricao'], ENT_QUOTES, 'UTF-8'); ?></p>

                                <div class="card-footer">
                                    <span class="card-price">R$ <?= number_format($produto['preco'], 2, ',', '.'); ?></span>
                                    <a href="../Produto/produto.php?id=<?= $produto['id_produto']; ?>" class="btn-add">Ver produto</a>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else : ?>
                <div class="empty-state">
                    <span class="empty-label">Sem resultados para:</span>
                    <span class="empty-search">"<?= $titlePesquisa ?>"</span>
                    <span class="empty-punctuation">.</span>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>