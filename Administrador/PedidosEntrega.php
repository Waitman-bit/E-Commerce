<?php
/**
 * PedidosEntrega.php
 *
 * NOVA página dentro de Administrador/ — não substitui nem altera Adm.php.
 * Lista pedidos/entregas para o administrador organizar os envios.
 *
 * Sobre "método de entrega" e "prazo estimado":
 * a tabela `entrega` não possui uma coluna própria para isso (só guarda o
 * valor do frete). Para não precisar alterar o banco, o método e o prazo
 * são INFERIDOS aqui comparando o valor de `entrega.frete` com a tabela de
 * preços por região (a mesma usada no checkout). Isso funciona bem para o
 * cenário atual, mas se a tabela de preços mudar no futuro, pedidos antigos
 * podem ser exibidos com o método "aproximado". Se quiser 100% de precisão
 * histórica, a alternativa é criar uma coluna `entrega.metodo_entrega`
 * (isso NÃO foi feito aqui — ficou combinado que eu pediria autorização
 * antes de alterar o banco).
 */

session_start();
require_once('../connection.php');
require_once('../Checkout/frete.php');

// ===== SEGURANÇA: SOMENTE ADMIN =====
if (!isset($_SESSION['id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: ../Login/Login.php');
    exit;
}

function formatarPreco($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

/**
 * Tenta descobrir qual método de entrega (normal/expressa/retirada) gerou
 * o valor de frete gravado, comparando com a tabela de preços atual.
 */
function inferirMetodoEntrega($estado, $freteGravado)
{
    $opcoes = titan_opcoes_frete($estado);

    if ($opcoes === false) {
        return ['metodo' => 'Indefinido', 'prazo' => '-'];
    }

    $freteGravado = round((float) $freteGravado, 2);

    if ($freteGravado <= 0.001) {
        return ['metodo' => 'Retirada na loja', 'prazo' => $opcoes['retirada']['prazo']];
    }

    if (abs($freteGravado - $opcoes['normal']['valor']) < 0.01) {
        return ['metodo' => 'Normal', 'prazo' => $opcoes['normal']['prazo']];
    }

    if (abs($freteGravado - $opcoes['expressa']['valor']) < 0.01) {
        return ['metodo' => 'Expressa', 'prazo' => $opcoes['expressa']['prazo']];
    }

    return ['metodo' => 'Personalizado', 'prazo' => '-'];
}

// ===== ATUALIZAÇÃO DE STATUS DA ENTREGA (opcional, via formulário da própria página) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_status_entrega'])) {
    $idEntrega = (int) ($_POST['id_entrega'] ?? 0);
    $novoStatus = trim($_POST['novo_status'] ?? '');

    $statusPermitidos = ['Aguardando envio', 'Aguardando retirada', 'Em transporte', 'Entregue', 'Retirado', 'Cancelada'];

    if ($idEntrega > 0 && in_array($novoStatus, $statusPermitidos, true)) {
        $stmtUpdate = $conn->prepare('UPDATE entrega SET status = ? WHERE id_entrega = ?');
        $stmtUpdate->bind_param('si', $novoStatus, $idEntrega);
        $stmtUpdate->execute();
        $stmtUpdate->close();
    }

    header('Location: PedidosEntrega.php' . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

// ===== FILTROS (via GET) =====
$filtroEstado = trim($_GET['estado'] ?? '');
$filtroCidade = trim($_GET['cidade'] ?? '');
$filtroCep = trim($_GET['cep'] ?? '');
$filtroStatusEntrega = trim($_GET['status_entrega'] ?? '');
$filtroRegiao = trim($_GET['regiao'] ?? '');
$filtroMetodo = trim($_GET['metodo'] ?? '');

// ===== CONSULTA PRINCIPAL (pedido + usuario + entrega + pagamento) =====
$condicoes = [];
$parametros = [];
$tipos = '';

if ($filtroEstado !== '') {
    $condicoes[] = 'e.estado = ?';
    $parametros[] = $filtroEstado;
    $tipos .= 's';
}
if ($filtroCidade !== '') {
    $condicoes[] = 'e.cidade LIKE ?';
    $parametros[] = '%' . $filtroCidade . '%';
    $tipos .= 's';
}
if ($filtroCep !== '') {
    $condicoes[] = 'e.cep LIKE ?';
    $parametros[] = '%' . preg_replace('/\D/', '', $filtroCep) . '%';
    $tipos .= 's';
}
if ($filtroStatusEntrega !== '') {
    $condicoes[] = 'e.status = ?';
    $parametros[] = $filtroStatusEntrega;
    $tipos .= 's';
}

$sql = 'SELECT
            p.id_pedido, p.data_pedido, p.status_pedido, p.valor_total,
            u.nome AS cliente_nome, u.telefone AS cliente_telefone,
            e.id_entrega, e.endereco, e.estado, e.cidade, e.cep, e.status AS status_entrega, e.frete,
            pg.tipo AS tipo_pagamento, pg.status AS status_pagamento
        FROM pedido p
        JOIN usuario u ON u.id_usuario = p.id_usuario
        LEFT JOIN entrega e ON e.id_pedido = p.id_pedido
        LEFT JOIN pagamento pg ON pg.id_pedido = p.id_pedido';

if (!empty($condicoes)) {
    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
}
$sql .= ' ORDER BY p.data_pedido DESC';

$stmt = $conn->prepare($sql);
if (!empty($parametros)) {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ===== BUSCA OS ITENS DE TODOS OS PEDIDOS DE UMA VEZ (agrupados por id_pedido) =====
$itensPorPedido = [];
if (!empty($pedidos)) {
    $idsPedidos = array_column($pedidos, 'id_pedido');
    $placeholders = implode(',', array_fill(0, count($idsPedidos), '?'));
    $tiposIds = str_repeat('i', count($idsPedidos));

    $sqlItens = "SELECT ip.id_pedido, ip.quantidade, ip.preco_unitario, pr.nome
                 FROM item_pedido ip
                 JOIN produto pr ON pr.id_produto = ip.id_produto
                 WHERE ip.id_pedido IN ($placeholders)";

    $stmtItens = $conn->prepare($sqlItens);
    $stmtItens->bind_param($tiposIds, ...$idsPedidos);
    $stmtItens->execute();
    $resultadoItens = $stmtItens->get_result();

    while ($linha = $resultadoItens->fetch_assoc()) {
        $itensPorPedido[$linha['id_pedido']][] = $linha;
    }
    $stmtItens->close();
}

// ===== APLICA REGIÃO/MÉTODO (inferidos) E MONTA A LISTA FINAL =====
$listaFinal = [];
foreach ($pedidos as $pedido) {
    $regiao = $pedido['estado'] ? (titan_regiao_por_uf($pedido['estado']) ?? 'Desconhecida') : 'Desconhecida';
    $infoMetodo = $pedido['estado'] ? inferirMetodoEntrega($pedido['estado'], $pedido['frete']) : ['metodo' => 'Indefinido', 'prazo' => '-'];

    if ($filtroRegiao !== '' && $regiao !== $filtroRegiao) {
        continue;
    }
    if ($filtroMetodo !== '' && $infoMetodo['metodo'] !== $filtroMetodo) {
        continue;
    }

    $pedido['regiao'] = $regiao;
    $pedido['metodo_entrega'] = $infoMetodo['metodo'];
    $pedido['prazo_estimado'] = $infoMetodo['prazo'];
    $pedido['itens'] = $itensPorPedido[$pedido['id_pedido']] ?? [];

    $listaFinal[] = $pedido;
}

$statusEntregaDisponiveis = ['Aguardando envio', 'Aguardando retirada', 'Em transporte', 'Entregue', 'Retirado', 'Cancelada'];
$regioesDisponiveis = ['Sudeste', 'Sul', 'Centro-Oeste', 'Nordeste', 'Norte'];
$metodosDisponiveis = ['Normal', 'Expressa', 'Retirada na loja'];
$ufsDisponiveis = array_keys(TITAN_MAPA_REGIAO_UF);
sort($ufsDisponiveis);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos e Entregas - Admin</title>
    <link rel="stylesheet" href="PedidosEntrega.css">
</head>
<body>

<div class="topo-admin">
    <div>
        <h1>Pedidos e Entregas</h1>
        <p class="subtitulo"><?php echo count($listaFinal); ?> pedido(s) encontrado(s)</p>
    </div>
    <a href="Adm.php" class="link-voltar">&larr; Voltar ao painel</a>
</div>

<form method="GET" class="barra-filtros">
    <div class="filtro">
        <label>Região</label>
        <select name="regiao">
            <option value="">Todas</option>
            <?php foreach ($regioesDisponiveis as $r): ?>
                <option value="<?php echo $r; ?>" <?php echo $filtroRegiao === $r ? 'selected' : ''; ?>><?php echo $r; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filtro">
        <label>Estado (UF)</label>
        <select name="estado">
            <option value="">Todos</option>
            <?php foreach ($ufsDisponiveis as $uf): ?>
                <option value="<?php echo $uf; ?>" <?php echo $filtroEstado === $uf ? 'selected' : ''; ?>><?php echo $uf; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filtro">
        <label>Cidade</label>
        <input type="text" name="cidade" value="<?php echo htmlspecialchars($filtroCidade); ?>" placeholder="Ex: Taquaritinga">
    </div>
    <div class="filtro">
        <label>CEP</label>
        <input type="text" name="cep" value="<?php echo htmlspecialchars($filtroCep); ?>" placeholder="Ex: 15900">
    </div>
    <div class="filtro">
        <label>Status da entrega</label>
        <select name="status_entrega">
            <option value="">Todos</option>
            <?php foreach ($statusEntregaDisponiveis as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $filtroStatusEntrega === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filtro">
        <label>Método de entrega</label>
        <select name="metodo">
            <option value="">Todos</option>
            <?php foreach ($metodosDisponiveis as $m): ?>
                <option value="<?php echo $m; ?>" <?php echo $filtroMetodo === $m ? 'selected' : ''; ?>><?php echo $m; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn-filtrar">Filtrar</button>
    <a href="PedidosEntrega.php" class="btn-limpar">Limpar</a>
</form>

<div class="tabela-wrapper">
    <table class="tabela-pedidos">
        <thead>
            <tr>
                <th></th>
                <th>Pedido</th>
                <th>Cliente</th>
                <th>Cidade / UF</th>
                <th>Método</th>
                <th>Total</th>
                <th>Status entrega</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($listaFinal)): ?>
                <tr><td colspan="8" class="vazio">Nenhum pedido encontrado com esses filtros.</td></tr>
            <?php endif; ?>

            <?php foreach ($listaFinal as $pedido): ?>
                <?php
                    $textoProdutos = [];
                    foreach ($pedido['itens'] as $item) {
                        $textoProdutos[] = $item['quantidade'] . 'x ' . $item['nome'];
                    }
                    $textoCopia = "PEDIDO #{$pedido['id_pedido']}\n\n"
                        . "Cliente: {$pedido['cliente_nome']}\n"
                        . "Telefone: " . ($pedido['cliente_telefone'] ?: 'Não informado') . "\n\n"
                        . "Entrega:\n"
                        . ($pedido['endereco'] ?: '-') . "\n"
                        . ($pedido['cidade'] ?: '-') . " - " . ($pedido['estado'] ?: '-') . "\n"
                        . "CEP: " . ($pedido['cep'] ?: '-') . "\n\n"
                        . "Produtos:\n"
                        . implode("\n", $textoProdutos) . "\n\n"
                        . "Entrega: {$pedido['metodo_entrega']}";
                ?>
                <tr class="linha-pedido" data-alvo="detalhe-<?php echo $pedido['id_pedido']; ?>">
                    <td><button type="button" class="btn-expandir" aria-label="Expandir">+</button></td>
                    <td>#<?php echo $pedido['id_pedido']; ?><br><span class="data-pedido"><?php echo date('d/m/Y', strtotime($pedido['data_pedido'])); ?></span></td>
                    <td><?php echo htmlspecialchars($pedido['cliente_nome']); ?></td>
                    <td><?php echo htmlspecialchars(($pedido['cidade'] ?: '-') . ' / ' . ($pedido['estado'] ?: '-')); ?></td>
                    <td><span class="tag-metodo"><?php echo htmlspecialchars($pedido['metodo_entrega']); ?></span></td>
                    <td><?php echo formatarPreco($pedido['valor_total']); ?></td>
                    <td>
                        <span class="tag-status tag-status-<?php echo strtolower(str_replace(' ', '-', $pedido['status_entrega'] ?? 'sem-entrega')); ?>">
                            <?php echo htmlspecialchars($pedido['status_entrega'] ?? 'Sem entrega'); ?>
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn-copiar" data-texto="<?php echo htmlspecialchars($textoCopia, ENT_QUOTES); ?>">
                            Copiar dados
                        </button>
                    </td>
                </tr>
                <tr class="linha-detalhe" id="detalhe-<?php echo $pedido['id_pedido']; ?>" style="display:none;">
                    <td colspan="8">
                        <div class="detalhe-box">
                            <div class="detalhe-coluna">
                                <h4>Produtos</h4>
                                <ul>
                                    <?php foreach ($pedido['itens'] as $item): ?>
                                        <li><?php echo $item['quantidade']; ?>x <?php echo htmlspecialchars($item['nome']); ?> — <?php echo formatarPreco($item['preco_unitario'] * $item['quantidade']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="detalhe-coluna">
                                <h4>Endereço completo</h4>
                                <p><?php echo htmlspecialchars($pedido['endereco'] ?: '-'); ?></p>
                                <p><?php echo htmlspecialchars(($pedido['cidade'] ?: '-') . ' - ' . ($pedido['estado'] ?: '-')); ?> · CEP <?php echo htmlspecialchars($pedido['cep'] ?: '-'); ?></p>
                                <p>Frete: <?php echo formatarPreco($pedido['frete'] ?? 0); ?> · Prazo: <?php echo htmlspecialchars($pedido['prazo_estimado']); ?></p>
                                <p>Região: <?php echo htmlspecialchars($pedido['regiao']); ?></p>
                            </div>
                            <div class="detalhe-coluna">
                                <h4>Pagamento</h4>
                                <p><?php echo htmlspecialchars($pedido['tipo_pagamento'] ?? '-'); ?> · <?php echo htmlspecialchars($pedido['status_pagamento'] ?? '-'); ?></p>
                                <p>Status do pedido: <?php echo htmlspecialchars($pedido['status_pedido']); ?></p>

                                <?php if ($pedido['id_entrega']): ?>
                                <form method="POST" class="form-status-entrega">
                                    <input type="hidden" name="id_entrega" value="<?php echo $pedido['id_entrega']; ?>">
                                    <input type="hidden" name="atualizar_status_entrega" value="1">
                                    <label>Atualizar status da entrega</label>
                                    <select name="novo_status">
                                        <?php foreach ($statusEntregaDisponiveis as $s): ?>
                                            <option value="<?php echo $s; ?>" <?php echo $pedido['status_entrega'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit">Salvar</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="toastCopia" class="toast-copia">Dados copiados!</div>

<script src="PedidosEntrega.js"></script>
</body>
</html>
