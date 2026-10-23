<?php
require_once __DIR__ . '/../connection.php';

if (!isset($_SESSION['id']) || ($_SESSION['tipo'] ?? '') !== 'admin') {
    header('Location: ../Login/Login.php');
    exit;
}

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function redirecionar($mensagem, $tipo = 'success') {
    header('Location: Estoque.php?mensagem=' . urlencode($mensagem) . '&tipo=' . urlencode($tipo));
    exit;
}

if (empty($_SESSION['csrf_estoque'])) {
    $_SESSION['csrf_estoque'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $idProduto = filter_input(INPUT_POST, 'id_produto', FILTER_VALIDATE_INT);
    $operacao = $_POST['operacao'] ?? '';
    $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);

    if (isset($_POST['gerenciar_tamanhos'])) {
        $estoquesEnviados = $_POST['estoques'] ?? [];
        $novos = $_POST['novos_tamanhos'] ?? [];
        if (!$idProduto || !hash_equals($_SESSION['csrf_estoque'], $csrf) || !is_array($novos) || !is_array($estoquesEnviados)) {
            redirecionar('Dados de estoque invÃ¡lidos.', 'error');
        }
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare('SELECT id_tamanho FROM produto_tamanho WHERE id_produto = ? FOR UPDATE');
            $stmt->bind_param('i', $idProduto);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $atuais = [];
            while ($linha = $resultado->fetch_assoc()) {
                $atuais[(int) $linha['id_tamanho']] = true;
            }
            $stmt->close();

            $stmtValida = $conn->prepare('SELECT id_tamanho FROM tamanho WHERE id_tamanho = ?');
            $stmtAtualiza = $conn->prepare('UPDATE produto_tamanho SET estoque = ? WHERE id_produto = ? AND id_tamanho = ?');
            $stmtInsere = $conn->prepare('INSERT INTO produto_tamanho (id_produto, id_tamanho, estoque) VALUES (?, ?, ?)');
            $stmtRemove = $conn->prepare('DELETE FROM produto_tamanho WHERE id_produto = ? AND id_tamanho = ?');
            $permitidos = array_fill_keys(array_map('intval', $novos), true);
            $mantidos = [];
            foreach ($estoquesEnviados as $idTamanho => $estoqueTexto) {
                $idTamanho = filter_var($idTamanho, FILTER_VALIDATE_INT);
                $estoque = filter_var($estoqueTexto, FILTER_VALIDATE_INT);
                if (!$idTamanho || $estoque === false || $estoque < 0) {
                    throw new RuntimeException('Informe um estoque vÃ¡lido para cada tamanho.');
                }
                $stmtValida->bind_param('i', $idTamanho);
                $stmtValida->execute();
                if ($stmtValida->get_result()->num_rows !== 1) {
                    throw new RuntimeException('Tamanho nÃ£o encontrado.');
                }
                if (isset($atuais[$idTamanho])) {
                    $stmtAtualiza->bind_param('iii', $estoque, $idProduto, $idTamanho);
                    $stmtAtualiza->execute();
                    $mantidos[$idTamanho] = true;
                } elseif (isset($permitidos[$idTamanho])) {
                    $stmtInsere->bind_param('iii', $idProduto, $idTamanho, $estoque);
                    $stmtInsere->execute();
                    $mantidos[$idTamanho] = true;
                } else {
                    throw new RuntimeException('Tamanho novo invÃ¡lido.');
                }
            }
            foreach ($atuais as $idTamanho => $_) {
                if (!isset($mantidos[$idTamanho])) {
                    $stmtRemove->bind_param('ii', $idProduto, $idTamanho);
                    $stmtRemove->execute();
                }
            }
            $stmtValida->close();
            $stmtAtualiza->close();
            $stmtInsere->close();
            $stmtRemove->close();
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            redirecionar($e->getMessage(), 'error');
        }
        redirecionar('Estoques por tamanho atualizados com sucesso.');
    }

    if (!$idProduto || !hash_equals($_SESSION['csrf_estoque'], $csrf)) {
        redirecionar('Solicitação inválida. Atualize a página e tente novamente.', 'error');
    }

    if ($operacao === 'ajustar') {
        if ($quantidade === false || $quantidade < 0) {
            redirecionar('Informe uma quantidade válida para o estoque.', 'error');
        }
        $mensagem = 'Estoque atualizado com sucesso.';
    } elseif ($operacao === 'entrada' || $operacao === 'saida') {
        if ($quantidade === false || $quantidade < 1) {
            redirecionar('Informe uma quantidade maior que zero.', 'error');
        }
        $mensagem = $operacao === 'entrada' ? 'Entrada registrada com sucesso.' : 'Saída registrada com sucesso.';
    } else {
        redirecionar('Operação de estoque inválida.', 'error');
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('SELECT id_tamanho, estoque FROM produto_tamanho WHERE id_produto = ? ORDER BY estoque DESC, id_tamanho ASC FOR UPDATE');
        $stmt->bind_param('i', $idProduto);
        $stmt->execute();
        $itensEstoque = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if ($itensEstoque === []) {
            throw new RuntimeException('O produto não possui tamanhos cadastrados para receber estoque.');
        }

        $totalAtual = array_sum(array_map(static fn($item) => (int) $item['estoque'], $itensEstoque));
        if ($operacao === 'ajustar') {
            $operacao = $quantidade >= $totalAtual ? 'entrada' : 'saida';
            $quantidade = abs($quantidade - $totalAtual);
        }

        if ($quantidade > 0 && $operacao === 'entrada') {
            $idTamanho = (int) $itensEstoque[0]['id_tamanho'];
            $stmt = $conn->prepare('UPDATE produto_tamanho SET estoque = estoque + ? WHERE id_produto = ? AND id_tamanho = ?');
            $stmt->bind_param('iii', $quantidade, $idProduto, $idTamanho);
            $stmt->execute();
            $stmt->close();
        }

        if ($quantidade > 0 && $operacao === 'saida') {
            if ($totalAtual < $quantidade) {
                throw new RuntimeException('Estoque insuficiente para registrar a saída.');
            }

            $restante = $quantidade;
            foreach ($itensEstoque as $item) {
                if ($restante === 0) {
                    break;
                }
                $retirar = min($restante, (int) $item['estoque']);
                if ($retirar === 0) {
                    continue;
                }
                $idTamanho = (int) $item['id_tamanho'];
                $stmt = $conn->prepare('UPDATE produto_tamanho SET estoque = estoque - ? WHERE id_produto = ? AND id_tamanho = ? AND estoque >= ?');
                $stmt->bind_param('iiii', $retirar, $idProduto, $idTamanho, $retirar);
                $stmt->execute();
                if ($stmt->affected_rows !== 1) {
                    $stmt->close();
                    throw new RuntimeException('Não foi possível atualizar o estoque.');
                }
                $stmt->close();
                $restante -= $retirar;
            }
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        redirecionar($e->getMessage(), 'error');
    }
    redirecionar($mensagem);
}

$categoriaSelecionada = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT) ?: 0;
$busca = trim($_GET['busca'] ?? '');
$somenteBaixo = isset($_GET['baixo']) && $_GET['baixo'] === '1';

$resumo = $conn->query('SELECT COUNT(DISTINCT p.id_produto) AS produtos,
        COALESCE(SUM(pt.estoque), 0) AS unidades,
        COALESCE(SUM(CASE WHEN COALESCE(pt.estoque, 0) <= 5 THEN 1 ELSE 0 END), 0) AS baixo,
        COALESCE(SUM(CASE WHEN COALESCE(pt.estoque, 0) = 0 THEN 1 ELSE 0 END), 0) AS zerado,
        COALESCE(SUM(p.preco * COALESCE(pt.estoque, 0)), 0) AS valor
        FROM produto p
        LEFT JOIN produto_tamanho pt ON pt.id_produto = p.id_produto')->fetch_assoc();
$categorias = $conn->query('SELECT id_categoria, nome FROM categoria ORDER BY nome')->fetch_all(MYSQLI_ASSOC);

$sql = 'SELECT p.id_produto, p.nome, COALESCE(SUM(pt.estoque), 0) AS estoque, p.preco, p.imagem, c.nome AS categoria
        FROM produto p
        LEFT JOIN categoria c ON c.id_categoria = p.id_categoria
        LEFT JOIN produto_tamanho pt ON pt.id_produto = p.id_produto
        WHERE 1=1';
$tipos = '';
$parametros = [];
if ($categoriaSelecionada > 0) {
    $sql .= ' AND p.id_categoria = ?';
    $tipos .= 'i';
    $parametros[] = $categoriaSelecionada;
}
if ($busca !== '') {
    $sql .= ' AND (p.nome LIKE ? OR p.marca LIKE ?)';
    $tipos .= 'ss';
    $termoBusca = '%' . $busca . '%';
    $parametros[] = $termoBusca;
    $parametros[] = $termoBusca;
}
$sql .= ' GROUP BY p.id_produto, p.nome, p.preco, p.imagem, c.nome';
if ($somenteBaixo) {
    $sql .= ' HAVING COALESCE(SUM(pt.estoque), 0) <= 5';
}
$sql .= ' ORDER BY COALESCE(SUM(pt.estoque), 0) ASC, p.nome ASC';
$stmtProdutos = $conn->prepare($sql);
if ($tipos !== '') {
    $stmtProdutos->bind_param($tipos, ...$parametros);
}
$stmtProdutos->execute();
$produtos = $stmtProdutos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtProdutos->close();

$tamanhosPorProduto = [];
$resultadoTamanhosProduto = $conn->query('SELECT pt.id_produto, pt.id_tamanho, t.descricao, pt.estoque
    FROM produto_tamanho pt INNER JOIN tamanho t ON t.id_tamanho = pt.id_tamanho
    ORDER BY t.id_tamanho');
while ($tamanhoProduto = $resultadoTamanhosProduto->fetch_assoc()) {
    $tamanhosPorProduto[(int) $tamanhoProduto['id_produto']][] = [
        'id' => (int) $tamanhoProduto['id_tamanho'],
        'descricao' => $tamanhoProduto['descricao'],
        'estoque' => (int) $tamanhoProduto['estoque'],
    ];
}
$todosTamanhos = $conn->query('SELECT id_tamanho, descricao FROM tamanho ORDER BY id_tamanho')->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" href="../logoicon.ico" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque | Titan Sports</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="Estoque.css">
</head>
<body>
    <header class="topbar">
        <a class="back-link" href="../Administrador/Adm.php"><i class="ti ti-arrow-left"></i> Painel administrativo</a>
        <div class="title"><span>GESTÃO</span><h1>Estoque</h1></div>
    </header>

    <main class="container">
        <section class="intro">
            <div><p>CONTROLE DE INVENTÁRIO</p><h2>Acompanhe os produtos da sua loja.</h2></div>
            <a class="outline-button" href="../EditarProdutos/EditarProdutos.php"><i class="ti ti-edit"></i> Editar produtos</a>
        </section>

        <?php if (isset($_GET['mensagem'])): ?>
            <div class="alert <?= ($_GET['tipo'] ?? '') === 'error' ? 'error' : 'success' ?>"><i class="ti <?= ($_GET['tipo'] ?? '') === 'error' ? 'ti-alert-circle' : 'ti-circle-check' ?>"></i><?= e($_GET['mensagem']) ?></div>
        <?php endif; ?>

        <section class="metrics" aria-label="Resumo do estoque">
            <article class="metric"><span class="metric-icon"><i class="ti ti-package"></i></span><div><small>Produtos cadastrados</small><strong><?= (int) $resumo['produtos'] ?></strong><em>itens diferentes</em></div></article>
            <article class="metric"><span class="metric-icon"><i class="ti ti-box"></i></span><div><small>Unidades disponíveis</small><strong><?= number_format((int) $resumo['unidades'], 0, ',', '.') ?></strong><em>em todo o estoque</em></div></article>
            <article class="metric"><span class="metric-icon"><i class="ti ti-cash"></i></span><div><small>Valor em estoque</small><strong>R$ <?= number_format((float) $resumo['valor'], 2, ',', '.') ?></strong><em>preço de venda</em></div></article>
            <article class="metric warning"><span class="metric-icon"><i class="ti ti-alert-triangle"></i></span><div><small>Requer atenção</small><strong><?= (int) $resumo['baixo'] ?></strong><em><?= (int) $resumo['zerado'] ?> sem estoque</em></div></article>
        </section>

        <section class="inventory-panel">
            <div class="panel-header"><div><h2>Inventário de produtos</h2><p><?= count($produtos) ?> produto(s) encontrado(s)</p></div></div>
            <form class="filters" method="GET">
                <label class="search"><i class="ti ti-search"></i><input type="search" name="busca" value="<?= e($busca) ?>" placeholder="Buscar por nome ou marca"></label>
                <select name="categoria" aria-label="Filtrar por categoria"><option value="0">Todas as categorias</option><?php foreach ($categorias as $categoria): ?><option value="<?= (int) $categoria['id_categoria'] ?>" <?= $categoriaSelecionada === (int) $categoria['id_categoria'] ? 'selected' : '' ?>><?= e($categoria['nome']) ?></option><?php endforeach; ?></select>
                <label class="low-filter"><input type="checkbox" name="baixo" value="1" <?= $somenteBaixo ? 'checked' : '' ?>><span>Estoque baixo</span></label>
                <button type="submit"><i class="ti ti-adjustments-horizontal"></i> Filtrar</button>
                <?php if ($busca !== '' || $categoriaSelecionada || $somenteBaixo): ?><a href="Estoque.php">Limpar</a><?php endif; ?>
            </form>

            <div class="table-wrap">
                <table>
                    <thead><tr><th>Produto</th><th>Categoria</th><th>Preço</th><th>Disponível</th><th>Status</th><th aria-label="Ações"></th></tr></thead>
                    <tbody>
                    <?php foreach ($produtos as $produto): $estoque = max(0, (int) $produto['estoque']); $status = $estoque === 0 ? 'Sem estoque' : ($estoque <= 5 ? 'Estoque baixo' : 'Em estoque'); $classe = $estoque === 0 ? 'out' : ($estoque <= 5 ? 'low' : 'ok'); ?>
                        <tr>
                            <td><div class="product"><img src="../ImagensProdutos/<?= e($produto['imagem']) ?>" alt=""><span><strong><?= e($produto['nome']) ?></strong><small>#<?= (int) $produto['id_produto'] ?></small></span></div></td>
                            <td><?= e($produto['categoria'] ?? 'Sem categoria') ?></td><td>R$ <?= number_format((float) $produto['preco'], 2, ',', '.') ?></td>
                            <td><strong class="stock-number"><?= $estoque ?></strong> un.</td><td><span class="status <?= $classe ?>"><i></i><?= $status ?></span></td>
                            <td><button class="adjust-button" type="button" data-id="<?= (int) $produto['id_produto'] ?>" data-name="<?= e($produto['nome']) ?>" data-stock="<?= $estoque ?>" data-sizes="<?= e(json_encode($tamanhosPorProduto[(int) $produto['id_produto']] ?? [], JSON_UNESCAPED_UNICODE)) ?>"><i class="ti ti-arrows-exchange"></i><span>Ajustar</span></button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!$produtos): ?><div class="empty"><i class="ti ti-package-off"></i><h3>Nenhum produto encontrado</h3><p>Altere os filtros ou cadastre um novo produto.</p></div><?php endif; ?>
            </div>
        </section>
    </main>

    <dialog id="stock-dialog" class="stock-dialog">
        <form method="POST" id="stock-form">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_estoque']) ?>"><input type="hidden" name="id_produto" id="dialog-product-id"><input type="hidden" name="gerenciar_tamanhos" value="1">
            <div class="dialog-heading"><div><span>AJUSTE DE ESTOQUE</span><h2 id="dialog-product-name"></h2></div><button type="button" class="close-dialog" aria-label="Fechar"><i class="ti ti-x"></i></button></div>

            <p class="current-stock">Estoque total atual: <strong id="dialog-current-stock"></strong> unidades</p>
            <div class="operation-tabs"><button type="button" data-operation="entrada"><i class="ti ti-plus"></i> Entrada</button><button type="button" data-operation="saida"><i class="ti ti-minus"></i> Saída</button><button type="button" class="active" data-operation="ajustar"><i class="ti ti-pencil"></i> Definir total</button></div>
            
            <div class="size-stock-list" id="size-stock-list"></div>
            <div class="add-size-row">
                <label for="add-size-select">Adicionar tamanho</label>
                <div><select id="add-size-select"><option value="">Selecione um tamanho</option><?php foreach ($todosTamanhos as $tamanho): ?><option value="<?= (int) $tamanho['id_tamanho'] ?>"><?= e($tamanho['descricao']) ?></option><?php endforeach; ?></select><button type="button" id="add-size-button" class="add-size-button"><i class="ti ti-plus"></i> Adicionar</button></div>
            </div>
            <div class="dialog-actions"><button type="button" class="cancel-dialog">Cancelar</button><button class="save-stock" type="submit"><i class="ti ti-device-floppy"></i> Salvar ajuste</button></div>
        </form>
    </dialog>
    <script src="Estoque.js?v=2"></script>
</body>
</html>
