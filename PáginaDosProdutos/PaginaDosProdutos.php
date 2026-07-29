<?php
require_once __DIR__ . '/../connection.php';

$paramCategoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

$mapaCategorias = [
    'futebol' => 'Futebol',
    'basquete' => 'Basquete',
    'corrida' => 'Corrida',
    'musculacao' => 'Musculação',
    'natacao' => 'Natação',
    'artes-marciais' => 'Artes Marciais',
    'suplementos' => 'Suplementos',
    'vestuario' => 'Vestuário',
    'acessorios' => 'Acessórios'
];

$nomeCategoria = '';
if ($paramCategoria !== '') {
    $slug = strtolower($paramCategoria);
    $slug = str_replace([' ', 'ç'], ['-', 'c'], $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');

    if (isset($mapaCategorias[$slug])) {
        $nomeCategoria = $mapaCategorias[$slug];
    } else {
        $nomeCategoria = $paramCategoria;
    }
}

if ($nomeCategoria !== '') {
    $stmt = $conn->prepare(
        "SELECT p.*, c.nome AS categoria_nome
         FROM produto p
         INNER JOIN categoria c ON p.id_categoria = c.id_categoria
         WHERE c.nome = ?
         ORDER BY p.nome ASC"
    );

    $stmt->bind_param('s', $nomeCategoria);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $tituloPagina = 'Produtos de ' . $nomeCategoria;
} else {
    $resultado = $conn->query(
        "SELECT p.*, c.nome AS categoria_nome
         FROM produto p
         INNER JOIN categoria c ON p.id_categoria = c.id_categoria
         ORDER BY c.nome, p.nome ASC"
    );
    $tituloPagina = 'Todos os produtos';
}

$categorias = $conn->query('SELECT nome FROM categoria ORDER BY nome ASC');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos</title>
    <?php require_once __DIR__ . '/../NavBar/Navbar.php'; ?>
    <link rel="stylesheet" href="PaginaDosProdutos.css">
</head>
<body>
    <main class="produtos-page">
        <section class="hero">
            <h1><?php echo htmlspecialchars($tituloPagina); ?></h1>
        </section>

        <section class="categorias-bar">
            <a class="cat-chip <?php echo $nomeCategoria === '' ? 'active' : ''; ?>" href="PaginaDosProdutos.php">Todos</a>
            <?php if ($categorias && $categorias->num_rows > 0): ?>
                <?php while ($categoria = $categorias->fetch_assoc()): ?>
                    <?php $categoriaNome = $categoria['nome']; ?>
                    <a class="cat-chip <?php echo $nomeCategoria === $categoriaNome ? 'active' : ''; ?>"
                       href="PaginaDosProdutos.php?categoria=<?php echo rawurlencode($categoriaNome); ?>">
                        <?php echo htmlspecialchars($categoriaNome); ?>
                    </a>
                <?php endwhile; ?>
            <?php endif; ?>
        </section>

        <section class="produtos-section">
            <?php if ($resultado && $resultado->num_rows > 0): ?>
                <div class="cards-grid">
                    <?php while ($produto = $resultado->fetch_assoc()): ?>
                        <article class="product-card">
                            <div class="card-img">
                                <img src="../ImagensProdutos/<?php echo htmlspecialchars($produto['imagem']); ?>"
                                     alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                            </div>
                            <div class="card-body">
                                <span class="card-category"><?php echo htmlspecialchars($produto['categoria_nome']); ?></span>
                                <h3><?php echo htmlspecialchars($produto['nome']); ?></h3>
                                <p class="card-description"><?php echo htmlspecialchars($produto['descricao']); ?></p>
                                <div class="card-footer">
                                    <span class="card-price">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></span>
                                    <a href="../Produto/produto.php?id=<?php echo intval($produto['id_produto']); ?>" class="btn-add">Ver produto</a>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">Nenhum produto encontrado para esta categoria.</div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>