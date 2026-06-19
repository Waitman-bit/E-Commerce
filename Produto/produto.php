<?php
/**
 * SportZone - Página de Detalhes do Produto
 * Exibe informações detalhadas de um produto específico
 */
session_start();

// ===== CONFIGURAÇÃO DO BANCO DE DADOS =====
$host = 'localhost';
$dbname = 'sportzone';
$dbuser = 'root';
$dbpass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados.");
}

// ===== VALIDAÇÃO DO ID RECEBIDO PELA URL =====
$idProduto = isset($_GET['id']) ? intval($_GET['id']) : 0;

$erro = null;
$produto = null;

if ($idProduto <= 0) {
    $erro = "ID inválido. Não foi possível identificar o produto.";
} else {
    // ===== BUSCA DO PRODUTO COM PREPARED STATEMENT =====
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = :id");
    $stmt->bindParam(':id', $idProduto, PDO::PARAM_INT);
    $stmt->execute();
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        $erro = "Produto não encontrado ou removido do catálogo.";
    }
}

// ===== SE HOUVER ERRO, EXIBE PÁGINA DE ERRO E ENCERRA =====
if ($erro) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Erro - SportZone</title>
        <link rel="stylesheet" href="produto.css">
    </head>
    <body>
        <div class="erro-container">
            <h1 class="erro-titulo">⚠️ Ops!</h1>
            <p class="erro-mensagem"><?php echo htmlspecialchars($erro); ?></p>
            <a href="produtos.php" class="btn-voltar">Voltar para Produtos</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ===== VERIFICAÇÃO DE LOGIN =====
$logado = isset($_SESSION['id']);
$isAdmin = $logado && isset($_SESSION['tipo']) && $_SESSION['tipo'] == 'admin';

// ===== STATUS DE ESTOQUE =====
$estoque = (int) $produto['estoque'];
if ($estoque <= 0) {
    $statusEstoque = "Esgotado";
    $statusClasse = "status-esgotado";
} elseif ($estoque <= 5) {
    $statusEstoque = "Poucas unidades";
    $statusClasse = "status-pouco";
} else {
    $statusEstoque = "Em estoque";
    $statusClasse = "status-disponivel";
}

// ===== FORMATAÇÃO DO PREÇO EM REAIS =====
$precoFormatado = "R$ " . number_format($produto['preco'], 2, ',', '.');

// ===== BUSCA DE PRODUTOS RELACIONADOS (mesma categoria, exceto o atual) =====
$stmtRel = $pdo->prepare(
    "SELECT id, nome, preco, imagem FROM produtos 
     WHERE categoria = :categoria AND id != :id 
     LIMIT 4"
);
$stmtRel->bindParam(':categoria', $produto['categoria']);
$stmtRel->bindParam(':id', $idProduto, PDO::PARAM_INT);
$stmtRel->execute();
$relacionados = $stmtRel->fetchAll(PDO::FETCH_ASSOC);

// Avaliação fixa de exemplo (poderia vir do banco em uma tabela de avaliações)
$avaliacaoEstrelas = 4;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($produto['nome']); ?> - SportZone</title>
    <link rel="stylesheet" href="produto.css">
</head>
<body>

<!-- ===== NAVBAR ===== -->
<header class="navbar">
    <div class="navbar-logo">SPORT<span>ZONE</span></div>
    <nav class="navbar-links">
        <a href="index.php">Início</a>
        <a href="produtos.php">Produtos</a>
        <a href="carrinho.php">Carrinho</a>
        <a href="perfil.php">Perfil</a>
        <?php if ($isAdmin): ?>
            <a href="../Admin/painel.php" class="link-admin">Painel Admin</a>
        <?php endif; ?>
    </nav>
</header>

<!-- ===== CONTEÚDO PRINCIPAL DO PRODUTO ===== -->
<main class="produto-container">

    <section class="produto-grid">

        <!-- COLUNA ESQUERDA: IMAGEM -->
        <div class="produto-imagem-box">
            <img src="<?php echo htmlspecialchars($produto['imagem']); ?>"
                 alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                 class="produto-imagem-principal">
        </div>

        <!-- COLUNA DIREITA: INFORMAÇÕES -->
        <div class="produto-info-box">
            <span class="produto-categoria"><?php echo htmlspecialchars($produto['categoria']); ?></span>
            <h1 class="produto-nome"><?php echo htmlspecialchars($produto['nome']); ?></h1>

            <!-- Avaliação em estrelas -->
            <div class="produto-estrelas">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="<?php echo $i <= $avaliacaoEstrelas ? 'estrela-ativa' : 'estrela-inativa'; ?>">★</span>
                <?php endfor; ?>
                <span class="estrela-numero">(<?php echo $avaliacaoEstrelas; ?>.0)</span>
            </div>

            <p class="produto-preco"><?php echo $precoFormatado; ?></p>

            <p class="produto-estoque-status <?php echo $statusClasse; ?>">
                <?php echo $statusEstoque; ?> 
                <?php if ($estoque > 0): ?>
                    (<?php echo $estoque; ?> unid.)
                <?php endif; ?>
            </p>

            <?php if ($logado): ?>
                <!-- ===== USUÁRIO LOGADO: COMPRA LIBERADA ===== -->
                <div class="produto-quantidade">
                    <label for="quantidade">Quantidade:</label>
                    <input type="number" id="quantidade" name="quantidade"
                           value="1" min="1" max="<?php echo $estoque; ?>"
                           <?php echo $estoque <= 0 ? 'disabled' : ''; ?>>
                </div>

                <div class="produto-botoes">
                    <button id="btnComprarAgora"
                            class="btn btn-amarelo"
                            data-id="<?php echo $produto['id']; ?>"
                            <?php echo $estoque <= 0 ? 'disabled' : ''; ?>>
                        Comprar Agora
                    </button>

                    <button id="btnAdicionarCarrinho"
                            class="btn btn-outline"
                            data-id="<?php echo $produto['id']; ?>"
                            <?php echo $estoque <= 0 ? 'disabled' : ''; ?>>
                        Adicionar ao Carrinho
                    </button>
                </div>
            <?php else: ?>
                <!-- ===== USUÁRIO NÃO LOGADO: BOTÕES DESABILITADOS ===== -->
                <div class="produto-botoes">
                    <button class="btn btn-amarelo" disabled>Comprar Agora</button>
                    <button class="btn btn-outline" disabled>Adicionar ao Carrinho</button>
                </div>
                <p class="aviso-login">Você precisa estar logado para comprar este produto.</p>
                <a href="../Login/login.php" class="btn btn-amarelo btn-login">Fazer Login</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===== DESCRIÇÃO COMPLETA ===== -->
    <section class="produto-descricao">
        <h2>Descrição do Produto</h2>
        <p><?php echo nl2br(htmlspecialchars($produto['descricao'])); ?></p>
    </section>

    <!-- ===== PRODUTOS RELACIONADOS ===== -->
    <?php if (count($relacionados) > 0): ?>
    <section class="produtos-relacionados">
        <h2>Produtos Relacionados</h2>
        <div class="relacionados-grid">
            <?php foreach ($relacionados as $rel): ?>
                <div class="card-relacionado">
                    <img src="<?php echo htmlspecialchars($rel['imagem']); ?>"
                         alt="<?php echo htmlspecialchars($rel['nome']); ?>">
                    <h3><?php echo htmlspecialchars($rel['nome']); ?></h3>
                    <p class="card-preco">R$ <?php echo number_format($rel['preco'], 2, ',', '.'); ?></p>
                    <a href="produto.php?id=<?php echo intval($rel['id']); ?>" class="btn btn-outline btn-pequeno">
                        Ver Produto
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</main>

<!-- ===== TOAST DE NOTIFICAÇÃO (usado pelo JS) ===== -->
<div id="toast" class="toast"></div>

<!-- Passa o ID logado para o JS de forma segura -->
<script>
    const USUARIO_LOGADO = <?php echo $logado ? 'true' : 'false'; ?>;
</script>
<script src="produto.js"></script>
</body>
</html>