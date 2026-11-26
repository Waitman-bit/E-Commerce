<?php
require_once __DIR__ . '/../session_config.php';
require_once __DIR__ . '/../asaas_config.php';
titan_start_session();

if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../Login/Login.php");
    exit();
}

require_once __DIR__ . '/../connection.php';

// Conta somente os usuários cadastrados como clientes (não inclui administradores).
$totalClientes = 0;
$stmtClientes = $conn->prepare("SELECT COUNT(*) AS total FROM usuario WHERE tipo = 'cliente'");
if ($stmtClientes) {
    $stmtClientes->execute();
    $resultadoClientes = $stmtClientes->get_result()->fetch_assoc();
    $totalClientes = (int) ($resultadoClientes['total'] ?? 0);
    $stmtClientes->close();
}

// Compara os cadastros deste mês com os do mês anterior.
$novosClientesMes = 0;
$novosClientesMesAnterior = 0;
$usuarioTemDataCadastro = false;
$colunaDataCadastro = $conn->query("SHOW COLUMNS FROM usuario LIKE 'data_cadastro'");
if ($colunaDataCadastro && $colunaDataCadastro->num_rows > 0) {
    $usuarioTemDataCadastro = true;
}

if ($usuarioTemDataCadastro) {
    $stmtCrescimento = $conn->prepare(
        "SELECT
            SUM(data_cadastro >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS mes_atual,
            SUM(data_cadastro >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01')
                AND data_cadastro < DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS mes_anterior
         FROM usuario
         WHERE tipo = 'cliente'"
    );
    if ($stmtCrescimento) {
        $stmtCrescimento->execute();
        $crescimento = $stmtCrescimento->get_result()->fetch_assoc();
        $novosClientesMes = (int) ($crescimento['mes_atual'] ?? 0);
        $novosClientesMesAnterior = (int) ($crescimento['mes_anterior'] ?? 0);
        $stmtCrescimento->close();
    }
}

if ($novosClientesMesAnterior > 0) {
    $percentualClientes = round((($novosClientesMes - $novosClientesMesAnterior) / $novosClientesMesAnterior) * 100);
    $textoCrescimentoClientes = sprintf('%+d%% em relação ao mês anterior', $percentualClientes);
    $classeCrescimentoClientes = $percentualClientes >= 0 ? 'up' : 'warn';
    $iconeCrescimentoClientes = $percentualClientes >= 0 ? 'ti-trending-up' : 'ti-trending-down';
} else {
    $textoCrescimentoClientes = $novosClientesMes === 1
        ? '1 novo cliente este mês'
        : $novosClientesMes . ' novos clientes este mês';
    $classeCrescimentoClientes = $novosClientesMes > 0 ? 'up' : 'warn';
    $iconeCrescimentoClientes = $novosClientesMes > 0 ? 'ti-trending-up' : 'ti-minus';
}

// Soma os itens vendidos por categoria para o gráfico do painel.
$categoriasMaisVendidas = [];
$sqlCategorias = "SELECT c.nome, SUM(ip.quantidade) AS quantidade_vendida
                  FROM item_pedido ip
                  INNER JOIN produto p ON p.id_produto = ip.id_produto
                  INNER JOIN categoria c ON c.id_categoria = p.id_categoria
                  INNER JOIN pedido pe ON pe.id_pedido = ip.id_pedido
                  WHERE pe.status_pedido IN ('Pago', 'Confirmado')
                  GROUP BY c.id_categoria, c.nome
                  ORDER BY quantidade_vendida DESC";

$resultadoCategorias = $conn->query($sqlCategorias);
if ($resultadoCategorias) {
    while ($categoria = $resultadoCategorias->fetch_assoc()) {
        $categoriasMaisVendidas[] = [
            'nome' => $categoria['nome'],
            'quantidade' => (int) $categoria['quantidade_vendida'],
        ];
    }
}

// Resumo do inventário para o card de estoque.
$resumoEstoque = [
    'produtos' => 0,
    'unidades' => 0,
    'baixo' => 0,
];
$resultadoEstoque = $conn->query(
    'SELECT
        COUNT(*) AS produtos,
        COALESCE(SUM(estoque_produto), 0) AS unidades,
        COALESCE(SUM(CASE WHEN estoque_produto <= 5 THEN 1 ELSE 0 END), 0) AS baixo
     FROM (
        SELECT p.id_produto, COALESCE(SUM(pt.estoque), 0) AS estoque_produto
        FROM produto p
        LEFT JOIN produto_tamanho pt ON pt.id_produto = p.id_produto
        GROUP BY p.id_produto
     ) AS estoque_por_produto'
);
if ($resultadoEstoque) {
    $resumoEstoque = $resultadoEstoque->fetch_assoc();
}

$totalProdutosBaixo = (int) ($resumoEstoque['baixo'] ?? 0);
if ($totalProdutosBaixo > 0) {
    $textoEstoque = $totalProdutosBaixo === 1
        ? '1 produto com estoque baixo'
        : $totalProdutosBaixo . ' produtos com estoque baixo';
    $classeEstoque = 'warn';
    $iconeEstoque = 'ti-alert-triangle';
} else {
    $textoEstoque = (int) ($resumoEstoque['produtos'] ?? 0) . ' produtos cadastrados';
    $classeEstoque = 'up';
    $iconeEstoque = 'ti-circle-check';
}

// Total de pedidos e comparação entre o mês atual e o mês anterior.
$resumoPedidos = [
    'total' => 0,
    'mes_atual' => 0,
    'mes_anterior' => 0,
];
$resultadoPedidos = $conn->query(
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(data_pedido >= DATE_FORMAT(CURDATE(), '%Y-%m-01')), 0) AS mes_atual,
        COALESCE(SUM(data_pedido >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01')
            AND data_pedido < DATE_FORMAT(CURDATE(), '%Y-%m-01')), 0) AS mes_anterior
     FROM pedido"
);
if ($resultadoPedidos) {
    $resumoPedidos = $resultadoPedidos->fetch_assoc();
}

$pedidosMes = (int) ($resumoPedidos['mes_atual'] ?? 0);
$pedidosMesAnterior = (int) ($resumoPedidos['mes_anterior'] ?? 0);
if ($pedidosMesAnterior > 0) {
    $percentualPedidos = round((($pedidosMes - $pedidosMesAnterior) / $pedidosMesAnterior) * 100);
    $textoCrescimentoPedidos = sprintf('%+d%% em relação ao mês anterior', $percentualPedidos);
    $classeCrescimentoPedidos = $percentualPedidos >= 0 ? 'up' : 'warn';
    $iconeCrescimentoPedidos = $percentualPedidos >= 0 ? 'ti-trending-up' : 'ti-trending-down';
} else {
    $textoCrescimentoPedidos = $pedidosMes === 1
        ? '1 pedido este mês'
        : $pedidosMes . ' pedidos este mês';
    $classeCrescimentoPedidos = $pedidosMes > 0 ? 'up' : 'warn';
    $iconeCrescimentoPedidos = $pedidosMes > 0 ? 'ti-trending-up' : 'ti-minus';
}

// Receita paga: soma TODAS as cobranças recebidas (sem filtro de período).
function titan_asaas_receita_total(): ?array
{
    $total = 0.0;
    $qtd = 0;
    $offset = 0;

    do {
        $query = http_build_query([
            'status' => 'RECEIVED',
            'limit'  => 100,
            'offset' => $offset,
        ]);

        [$status, $pagina] = titan_asaas_request('GET', '/payments?' . $query);
        if ($status < 200 || $status >= 300 || $pagina === null) {
            return null; // API falhou, sinaliza pro fallback
        }

        foreach ($pagina['data'] ?? [] as $pagamento) {
            $total += (float) $pagamento['value'];
            $qtd++;
        }

        $temMais = $pagina['hasMore'] ?? false;
        $offset += 100;
    } while ($temMais);

    return ['total' => $total, 'qtd' => $qtd];
}

function titan_receita_banco_total(mysqli $conn): array
{
    $resultado = $conn->query(
        'SELECT COALESCE(SUM(valor_pago), 0) AS total, COUNT(*) AS qtd
         FROM contas_receber
         WHERE data_pagamento IS NOT NULL'
    );
    $linha = $resultado->fetch_assoc();

    return ['total' => (float) $linha['total'], 'qtd' => (int) $linha['qtd']];
}

$receita = titan_asaas_receita_total();

$usouFallback = false;
if ($receita === null) {
    $usouFallback = true;
    $receita = titan_receita_banco_total($conn);
}

$receitaFormatada = 'R$ ' . number_format($receita['total'], 2, ',', '.');
$textoCrescimentoReceita = $receita['qtd'] === 1
    ? '1 cobrança paga'
    : $receita['qtd'] . ' cobranças pagas';
$classeCrescimentoReceita = $receita['total'] > 0 ? 'up' : 'warn';
$iconeCrescimentoReceita = $receita['total'] > 0 ? 'ti-circle-check' : 'ti-minus';

// Gráfico de receita mensal dos últimos meses
$mesesAbrev = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
$receitaMensal = [];
for ($i = 11; $i >= 0; $i--) {
    $timestamp = strtotime(date('Y-m-01') . " -$i month");
    $receitaMensal[date('Y-m', $timestamp)] = [
        'mes' => $mesesAbrev[(int) date('n', $timestamp) - 1] . '/' . date('y', $timestamp),
        'total' => 0.0,
    ];
}

$resultadoReceitaMensal = $conn->query(
    "SELECT DATE_FORMAT(data_pagamento, '%Y-%m') AS mes, SUM(valor_pago) AS total
     FROM contas_receber
     WHERE data_pagamento IS NOT NULL
       AND data_pagamento >= DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH, '%Y-%m-01')
     GROUP BY mes"
);
if ($resultadoReceitaMensal) {
    while ($linha = $resultadoReceitaMensal->fetch_assoc()) {
        if (isset($receitaMensal[$linha['mes']])) {
            $receitaMensal[$linha['mes']]['total'] = (float) $linha['total'];
        }
    }
}
$receitaMensal = array_values($receitaMensal);

// Pedidos por hora do dia com mais pedidos.
$linhaDia = $conn->query("SELECT DATE(MAX(data_pedido)) AS dia FROM pedido")->fetch_assoc();
$diaPedidosHora = $linhaDia['dia'] ?? date('Y-m-d');
$pedidosPorHora = array_fill(0, 24, 0);
$stmtHoras = $conn->prepare("SELECT HOUR(data_pedido) AS hora, COUNT(*) AS total FROM pedido WHERE DATE(data_pedido) = ? GROUP BY hora");
if ($stmtHoras) {
    $stmtHoras->bind_param('s', $diaPedidosHora);
    $stmtHoras->execute();
    $resultadoHoras = $stmtHoras->get_result();
    while ($linha = $resultadoHoras->fetch_assoc()) {
        $pedidosPorHora[(int) $linha['hora']] = (int) $linha['total'];
    }
    $stmtHoras->close();
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" href="../logoicon.ico" type="image/png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="Adm.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" />
</head>
<body>
  <div class="dash">
    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="adm-profile">
        <div class="adm-avatar">Adm</div>
        <div class="adm-name">Administrador</div>
        <div class="adm-role">Administrador</div>
        <div class="adm-badge">
          <i class="ti ti-shield-check"></i> Acesso total
        </div>
      </div>

      <nav class="nav">
        <button class="nav-item active">
          <i class="ti ti-layout-dashboard"></i> Dashboard
        </button>
        <button class="nav-item">
          <a href="../EditarProdutos/EditarProdutos.php">
            <i class="ti ti-shopping-bag"></i> Editar Produtos
          </a>
        </button>
        <button class="nav-item">
         <a href="../CadastroDeProdutos/CadastroDeProduto.php"> <i class="ti ti-box"></i> Cadastrar Produtos</a>
        </button>
        <button class="nav-item">
          <a href="../Estoque/Estoque.php">
            <i class="ti ti-truck-delivery"></i> Estoque
          </a>
        </button>
        <a href="PedidosEntrega.php" class="nav-item">
          <i class="ti ti-package-export"></i> Pedidos e Entregas
        </a>
        <button class="nav-item">
          <a href="../Index/Index.php">
            <i class="ti ti-home"></i> Início
          </a>
        </button>
      </nav>
        <div class="sidebar-footer">
            <a href="../Login/Login.php" class="nav-item nav-logout">
             <i class="ti ti-logout"></i> Sair
                </a>
            </div>
        </aside>

    <!-- CONTEÚDO PRINCIPAL -->
    <main class="main">

      <!-- TOPBAR -->
      <div class="topbar">
        <div>
          <div class="topbar-title">Visão Geral da Loja</div>
        </div>
      </div>

      <!-- MÉTRICAS -->
      <div class="metrics">
        <div class="metric-card">
          <div class="metric-label">
            <i class="ti ti-currency-dollar"></i> Receita Total
          </div>

         <div class="metric-val yellow"><?php echo htmlspecialchars($receitaFormatada); ?></div>
          <div class="metric-change <?php echo $classeCrescimentoReceita; ?>">
            <i class="ti <?php echo $iconeCrescimentoReceita; ?>"></i> <?php echo htmlspecialchars($textoCrescimentoReceita); ?>
          </div>
          <?php if ($usouFallback): ?>
          <div class="metric-change warn" style="font-size:11px;">
            <i class="ti ti-database"></i> Calculado localmente (API indisponível)
          </div>
          <?php endif; ?>
          </div>
          
        <div class="metric-card">
          <div class="metric-label">
            <i class="ti ti-shopping-cart"></i> Pedidos
          </div>
          <div class="metric-val"><?php echo number_format((int) ($resumoPedidos['total'] ?? 0), 0, ',', '.'); ?></div>
          <div class="metric-change <?php echo $classeCrescimentoPedidos; ?>">
            <i class="ti <?php echo $iconeCrescimentoPedidos; ?>"></i> <?php echo htmlspecialchars($textoCrescimentoPedidos); ?>
          </div>
        </div>
        <div class="metric-card">
          <div class="metric-label">
            <i class="ti ti-users"></i> Clientes
          </div>
          <div class="metric-val"><?php echo number_format($totalClientes, 0, ',', '.'); ?></div>
          <div class="metric-change <?php echo $classeCrescimentoClientes; ?>">
            <i class="ti <?php echo $iconeCrescimentoClientes; ?>"></i> <?php echo htmlspecialchars($textoCrescimentoClientes); ?>
          </div>
        </div>
        <div class="metric-card">
          <div class="metric-label">
            <i class="ti ti-box"></i> Produtos em Estoque
          </div>
          <div class="metric-val"><?php echo number_format((int) ($resumoEstoque['unidades'] ?? 0), 0, ',', '.'); ?></div>
          <div class="metric-change <?php echo $classeEstoque; ?>">
            <i class="ti <?php echo $iconeEstoque; ?>"></i> <?php echo htmlspecialchars($textoEstoque); ?>
          </div>
        </div>
      </div>

      <!-- GRÁFICOS -->
      <div class="charts-row">
        <div class="chart-card">
          <div class="chart-title">Receita mensal</div>
         <div class="chart-title">
            <span class="chart-title-sub" id="diaPedidosLabel"></span>
        </div>
      <div class="legend-row">
         <span class="legend-item"><span class="leg-dashed"></span>Pedidos</span>
      </div>
          <div class="chart-wrapper">
            <canvas id="revenueChart" role="img" aria-label="Gráfico de receita mensal comparando 2025 e 2026">
              Receita de jan a mai: 2026 crescendo vs 2025.
            </canvas>
          </div>
          <div class="chart-mobile-table" aria-label="Tabela de receita mensal">
            <table>
              <thead>
                <tr><th>Mês</th><th>Receita</th></tr>
              </thead>
              <tbody>
                <?php foreach ($receitaMensal as $item): ?>
                <tr>
                  <td><?php echo htmlspecialchars($item['mes']); ?></td>
                  <td>R$ <?php echo number_format((float) $item['total'], 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="chart-card">
          <div class="chart-title">Categorias mais Vendidas</div>
          <div class="chart-wrapper">
            <canvas id="catChart" role="img" aria-label="Gráfico de rosca com categorias mais vendidas">
              Gráfico com as categorias mais vendidas.
            </canvas>
          </div>
          <div class="legend-row" id="catLegend" style="margin-top: 10px;"></div>
          <div class="chart-mobile-table" aria-label="Tabela de categorias mais vendidas">
            <table>
              <thead>
                <tr><th>Categoria</th><th>Qtd.</th></tr>
              </thead>
              <tbody>
                <?php if (empty($categoriasMaisVendidas)): ?>
                <tr><td colspan="2">Nenhuma venda confirmada ainda.</td></tr>
                <?php else: ?>
                  <?php foreach ($categoriasMaisVendidas as $categoria): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($categoria['nome']); ?></td>
                    <td><?php echo number_format((int) $categoria['quantidade'], 0, ',', '.'); ?></td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="charts-row">
  <div class="chart-card full">
    <div class="chart-title">
     Pedidos Por Hora — Hoje
    </div>
    <div style="position: relative; width: 100%; height: 200px;">
      <canvas id="trafficChart" role="img" aria-label="Gráfico de linha: visitantes e pedidos ao longo do dia de hoje">
      </canvas>
    </div>
    <div class="chart-mobile-table" aria-label="Tabela de pedidos por hora">
      <table>
        <thead>
          <tr><th>Hora</th><th>Pedidos</th></tr>
        </thead>
        <tbody>
          <?php foreach ($pedidosPorHora as $hora => $totalHora): ?>
          <tr>
            <td><?php echo str_pad((string) $hora, 2, '0', STR_PAD_LEFT); ?>h</td>
            <td><?php echo number_format((int) $totalHora, 0, ',', '.'); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
    </main>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
     window.dadosCategorias = <?php echo json_encode($categoriasMaisVendidas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
     window.receitaMensal = <?php echo json_encode($receitaMensal, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
     window.pedidosPorHora = <?php echo json_encode($pedidosPorHora); ?>;
     window.diaPedidosHora = <?php echo json_encode(date('d/m/Y', strtotime($diaPedidosHora))); ?>;
   </script>
  <script src="script.js?v=<?php echo filemtime(__DIR__ . '/script.js'); ?>"></script>
</body>
</html>

<?php
   
?>