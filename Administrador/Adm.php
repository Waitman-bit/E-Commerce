<?php
require_once __DIR__ . '/../session_config.php';
titan_start_session();

if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../Login/Login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
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
          <div class="topbar-date">Maio 2026 · atualizado agora</div>
        </div>
      </div>

      <!-- MÉTRICAS -->
      <div class="metrics">
        <div class="metric-card">
          <div class="metric-label">
            <i class="ti ti-currency-dollar"></i> Receita
          </div>
          <div class="metric-val yellow">R$47.2k</div>
          <div class="metric-change up">
            <i class="ti ti-trending-up"></i> +12% este mês
          </div>
        </div>
        <div class="metric-card">
          <div class="metric-label">
            <i class="ti ti-shopping-cart"></i> Pedidos
          </div>
          <div class="metric-val">1.340</div>
          <div class="metric-change up">
            <i class="ti ti-trending-up"></i> +8% este mês
          </div>
        </div>
        <div class="metric-card">
          <div class="metric-label">
            <i class="ti ti-users"></i> Clientes
          </div>
          <div class="metric-val">892</div>
          <div class="metric-change up">
            <i class="ti ti-trending-up"></i> +5% este mês
          </div>
        </div>
        <div class="metric-card">
          <div class="metric-label">
            <i class="ti ti-box"></i> Produtos em Estoque
          </div>
          <div class="metric-val">14</div>
          <div class="metric-change warn">
            <i class="ti ti-alert-triangle"></i> requer atenção
          </div>
        </div>
      </div>

      <!-- GRÁFICOS -->
      <div class="charts-row">
        <div class="chart-card">
          <div class="chart-title">Receita mensal</div>
          <div class="legend-row">
            <span class="legend-item">
              <span class="leg-sq" style="background:#F5C000"></span> 2026
            </span>
            <span class="legend-item">
              <span class="leg-sq" style="background:#3d3300"></span> 2025
            </span>
          </div>
          <div class="chart-wrapper">
            <canvas id="revenueChart" role="img" aria-label="Gráfico de receita mensal comparando 2025 e 2026">
              Receita de jan a mai: 2026 crescendo vs 2025.
            </canvas>
          </div>
        </div>

        <div class="chart-card">
          <div class="chart-title">Categorias mais Vendidas</div>
          <div class="chart-wrapper">
            <canvas id="catChart" role="img" aria-label="Gráfico de rosca com distribuição por categorias de produto">
              Eletrônicos 40%, Roupas 25%, Casa 20%, Outros 15%.
            </canvas>
          </div>
          <div class="legend-row" style="margin-top: 10px;">
            <span class="legend-item"><span class="leg-sq" style="background:#F5C000"></span> Eletrônicos 40%</span>
            <span class="legend-item"><span class="leg-sq" style="background:#a88800"></span> Roupas 25%</span>
            <span class="legend-item"><span class="leg-sq" style="background:#5a4800"></span> Casa 20%</span>
            <span class="legend-item"><span class="leg-sq" style="background:#2a2200"></span> Outros 15%</span>
          </div>
        </div>
      </div>
      <div class="charts-row">
  <div class="chart-card full">
    <div class="chart-title">
      Visitantes vs Pedidos — hoje
      <span class="chart-title-sub">por hora · 29 mai 2026</span>
    </div>
    <div class="legend-row">
      <span class="legend-item"><span class="leg-line"></span>Visitantes</span>
      <span class="legend-item"><span class="leg-dashed"></span>Pedidos</span>
    </div>
    <div style="position: relative; width: 100%; height: 200px;">
      <canvas id="trafficChart" role="img" aria-label="Gráfico de linha: visitantes e pedidos ao longo do dia de hoje">
        Pico de visitantes às 14h (320), pico de pedidos às 15h (98).
      </canvas>
    </div>
  </div>
</div>
    </main>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="script.js"></script>
</body>
</html>

<?php
   
?>
