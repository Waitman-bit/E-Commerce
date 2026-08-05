<?php
require_once '../connection.php';

if (!isset($_SESSION['id']) || ($_SESSION['tipo'] ?? '') !== 'admin') {
    header('Location: ../Login/Login.php');
    exit;
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirectToProduct($id, $message = '') {
    $url = 'EditarProdutos.php?id=' . (int) $id;
    if ($message !== '') {
        $url .= '&sucesso=' . urlencode($message);
    }
    header('Location: ' . $url);
    exit;
}

if (empty($_SESSION['csrf_editar_produto'])) {
    $_SESSION['csrf_editar_produto'] = bin2hex(random_bytes(32));
}

$erro = '';
$produtoSelecionado = null;
$idSelecionado = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idProduto = filter_input(INPUT_POST, 'id_produto', FILTER_VALIDATE_INT);
    $csrf = $_POST['csrf_token'] ?? '';

    if (!$idProduto || !hash_equals($_SESSION['csrf_editar_produto'], $csrf)) {
        $erro = 'Solicitação inválida. Atualize a página e tente novamente.';
    } else {
        $nome = trim($_POST['nome'] ?? '');
        $marca = trim($_POST['marca'] ?? '');
        $categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
        $genero = $_POST['genero'] ?? 'Unissex';
        $descricao = trim($_POST['descricao'] ?? '');
        $estoque = filter_input(INPUT_POST, 'estoque', FILTER_VALIDATE_INT);
        $dimensoes = str_replace(',', '.', trim($_POST['dimensoes'] ?? '0'));
        $peso = str_replace(',', '.', trim($_POST['peso'] ?? '0'));
        $preco = str_replace(',', '.', trim($_POST['preco'] ?? ''));

        $generosValidos = ['Masculino', 'Feminino', 'Unissex'];
        if ($nome === '' || mb_strlen($nome) > 150 || $marca === '' || mb_strlen($marca) > 20 || !$categoria ||
            !in_array($genero, $generosValidos, true) || $descricao === '' || mb_strlen($descricao) > 50 ||
            $estoque === false || $estoque < 0 || !is_numeric($dimensoes) || (float) $dimensoes < 0 ||
            !is_numeric($peso) || (float) $peso < 0 || !is_numeric($preco) || (float) $preco < 0) {
            $erro = 'Revise os campos obrigatórios e informe valores numéricos válidos.';
        } else {
            $stmtAtual = $conn->prepare('SELECT imagem FROM produto WHERE id_produto = ?');
            $stmtAtual->bind_param('i', $idProduto);
            $stmtAtual->execute();
            $produtoAtual = $stmtAtual->get_result()->fetch_assoc();
            $stmtAtual->close();

            if (!$produtoAtual) {
                $erro = 'Produto não encontrado.';
            } else {
                $imagem = $produtoAtual['imagem'];
                if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ($_FILES['imagem']['error'] !== UPLOAD_ERR_OK || $_FILES['imagem']['size'] > 5 * 1024 * 1024) {
                        $erro = 'Não foi possível enviar a imagem. O limite é de 5 MB.';
                    } else {
                        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['imagem']['tmp_name']);
                        $extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                        if (!isset($extensoes[$mime])) {
                            $erro = 'Envie uma imagem JPG, PNG ou WEBP válida.';
                        } else {
                            $imagem = 'produto_' . bin2hex(random_bytes(12)) . '.' . $extensoes[$mime];
                            if (!move_uploaded_file($_FILES['imagem']['tmp_name'], '../ImagensProdutos/' . $imagem)) {
                                $erro = 'Não foi possível salvar a nova imagem do produto.';
                            }
                        }
                    }
                }

                if ($erro === '') {
                    $stmt = $conn->prepare('UPDATE produto SET marca = ?, id_categoria = ?, dimensoes = ?, nome = ?, genero = ?, estoque = ?, descricao = ?, imagem = ?, peso = ?, preco = ? WHERE id_produto = ?');
                    $dimensoesNumero = (float) $dimensoes;
                    $pesoNumero = (float) $peso;
                    $precoNumero = (float) $preco;
                    $stmt->bind_param('sidssissddi', $marca, $categoria, $dimensoesNumero, $nome, $genero, $estoque, $descricao, $imagem, $pesoNumero, $precoNumero, $idProduto);
                    if ($stmt->execute()) {
                        $stmt->close();
                        redirectToProduct($idProduto, 'Produto atualizado com sucesso.');
                    }
                    $erro = 'Não foi possível atualizar o produto.';
                    $stmt->close();
                }
            }
        }
    }
    $idSelecionado = $idProduto ?: 0;
}

$categorias = $conn->query('SELECT id_categoria, nome FROM categoria ORDER BY nome')->fetch_all(MYSQLI_ASSOC);
$produtos = $conn->query('SELECT p.id_produto, p.nome, p.preco, p.estoque, p.imagem, c.nome AS categoria FROM produto p LEFT JOIN categoria c ON c.id_categoria = p.id_categoria ORDER BY p.nome')->fetch_all(MYSQLI_ASSOC);

if ($idSelecionado > 0) {
    $stmt = $conn->prepare('SELECT * FROM produto WHERE id_produto = ?');
    $stmt->bind_param('i', $idSelecionado);
    $stmt->execute();
    $produtoSelecionado = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$produtoSelecionado && $erro === '') {
        $erro = 'Produto não encontrado.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produtos | Titan Sports</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="EditarProdutos.css">
</head>
<body>
    <header class="topbar">
        <a class="brand" href="../Administrador/Adm.php"><i class="ti ti-arrow-left"></i> Painel administrativo</a>
        <h1>Editar produtos</h1>
        <a class="new-product" href="../CadastroDeProdutos/CadastroDeProduto.php"><i class="ti ti-plus"></i> Novo produto</a>
    </header>

    <main class="container">
        <?php if (isset($_GET['sucesso'])): ?>
            <div class="alert success"><i class="ti ti-circle-check"></i><?= e($_GET['sucesso']) ?></div>
        <?php endif; ?>
        <?php if ($erro !== ''): ?>
            <div class="alert error"><i class="ti ti-alert-circle"></i><?= e($erro) ?></div>
        <?php endif; ?>

        <section class="products-panel">
            <div class="section-heading">
                <div><span class="eyebrow">CATÁLOGO</span><h2>Selecione um produto</h2></div>
                <label class="search"><i class="ti ti-search"></i><input id="product-search" type="search" placeholder="Buscar produto"></label>
            </div>
            <div class="product-list" id="product-list">
                <?php foreach ($produtos as $item): ?>
                    <a class="product-card <?= $idSelecionado === (int) $item['id_produto'] ? 'selected' : '' ?>" href="EditarProdutos.php?id=<?= (int) $item['id_produto'] ?>" data-name="<?= e(mb_strtolower($item['nome'] . ' ' . $item['categoria'])) ?>">
                        <img src="../ImagensProdutos/<?= e($item['imagem']) ?>" alt="">
                        <span class="product-info"><strong><?= e($item['nome']) ?></strong><small><?= e($item['categoria'] ?? 'Sem categoria') ?> · <?= (int) $item['estoque'] ?> un.</small></span>
                        <span class="product-price">R$ <?= number_format((float) $item['preco'], 2, ',', '.') ?></span>
                        <i class="ti ti-chevron-right"></i>
                    </a>
                <?php endforeach; ?>
            </div>
            <p class="empty-search" id="empty-search" hidden>Nenhum produto encontrado.</p>
        </section>

        <section class="editor-panel <?= $produtoSelecionado ? '' : 'editor-empty' ?>">
            <?php if (!$produtoSelecionado): ?>
                <div class="empty-state"><i class="ti ti-edit"></i><h2>Escolha um produto</h2><p>Selecione um item da lista para visualizar e editar suas informações.</p></div>
            <?php else: ?>
                <div class="section-heading"><div><span class="eyebrow">EDIÇÃO</span><h2><?= e($produtoSelecionado['nome']) ?></h2></div><span class="id-label">#<?= (int) $produtoSelecionado['id_produto'] ?></span></div>
                <form method="POST" enctype="multipart/form-data" id="product-form">
                    <input type="hidden" name="id_produto" value="<?= (int) $produtoSelecionado['id_produto'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_editar_produto']) ?>">
                    <div class="edit-grid">
                        <div class="image-field">
                            <label for="imagem">Imagem do produto</label>
                            <label class="image-preview" for="imagem"><img id="preview-image" src="../ImagensProdutos/<?= e($produtoSelecionado['imagem']) ?>" alt="Imagem atual de <?= e($produtoSelecionado['nome']) ?>"><span><i class="ti ti-camera"></i> Alterar imagem</span></label>
                            <input id="imagem" name="imagem" type="file" accept="image/jpeg,image/png,image/webp" hidden>
                            <small>JPG, PNG ou WEBP · até 5 MB</small>
                        </div>
                        <div class="form-fields">
                            <div class="field full"><label for="nome">Nome *</label><input id="nome" name="nome" maxlength="150" required value="<?= e($produtoSelecionado['nome']) ?>"></div>
                            <div class="field"><label for="marca">Marca *</label><input id="marca" name="marca" maxlength="20" required value="<?= e($produtoSelecionado['marca']) ?>"></div>
                            <div class="field"><label for="id_categoria">Categoria *</label><select id="id_categoria" name="id_categoria" required><?php foreach ($categorias as $categoria): ?><option value="<?= (int) $categoria['id_categoria'] ?>" <?= (int) $categoria['id_categoria'] === (int) $produtoSelecionado['id_categoria'] ? 'selected' : '' ?>><?= e($categoria['nome']) ?></option><?php endforeach; ?></select></div>
                            <div class="field"><label for="genero">Gênero *</label><select id="genero" name="genero"><?php foreach (['Unissex', 'Masculino', 'Feminino'] as $genero): ?><option <?= $genero === $produtoSelecionado['genero'] ? 'selected' : '' ?>><?= $genero ?></option><?php endforeach; ?></select></div>
                            <div class="field"><label for="estoque">Estoque *</label><input id="estoque" name="estoque" type="number" min="0" required value="<?= (int) $produtoSelecionado['estoque'] ?>"></div>
                            <div class="field"><label for="preco">Preço (R$) *</label><input id="preco" name="preco" type="number" min="0" step="0.01" required value="<?= e(number_format((float) $produtoSelecionado['preco'], 2, '.', '')) ?>"></div>
                            <div class="field"><label for="peso">Peso (kg)</label><input id="peso" name="peso" type="number" min="0" step="0.01" required value="<?= e($produtoSelecionado['peso']) ?>"></div>
                            <div class="field"><label for="dimensoes">Dimensões (cm)</label><input id="dimensoes" name="dimensoes" type="number" min="0" step="0.01" required value="<?= e($produtoSelecionado['dimensoes']) ?>"></div>
                            <div class="field full"><label for="descricao">Descrição *</label><textarea id="descricao" name="descricao" maxlength="50" required><?= e($produtoSelecionado['descricao']) ?></textarea><small><span id="description-count">0</span>/50 caracteres</small></div>
                        </div>
                    </div>
                    <div class="form-actions"><a href="EditarProdutos.php" class="cancel">Cancelar</a><button type="submit"><i class="ti ti-device-floppy"></i> Salvar alterações</button></div>
                </form>
            <?php endif; ?>
        </section>
    </main>
    <script src="EditarProduto.js"></script>
</body>
</html>