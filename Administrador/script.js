// Gráfico da receita mensal
const revenueCtx = document.getElementById('revenueChart');

const receitaCtx = document.getElementById('revenueChart');
const receitaMensal = window.receitaMensal || [];

new Chart(receitaCtx, {
  type: 'bar',
  data: {
    labels: receitaMensal.map((item) => item.mes),
    datasets: [{
      label: 'Receita',
      data: receitaMensal.map((item) => item.total),
      backgroundColor: '#F5C000',
      borderRadius: 4,
      barPercentage: 0.55,
    }],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#0d0d0c',
        titleColor: '#f5f5f0',
        bodyColor: '#888780',
        borderColor: '#2a2a28',
        borderWidth: 1,
        callbacks: {
          label: (ctx) => ' R$ ' + ctx.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 }),
        },
      },
    },
    scales: {
      x: { grid: { display: false }, ticks: { color: '#666663', font: { size: 11 } } },
      y: {
        grid: { color: 'rgba(255,255,255,0.05)' },
        ticks: { color: '#666663', font: { size: 11 }, callback: (v) => 'R$ ' + Number(v).toLocaleString('pt-BR') },
        beginAtZero: true,
      },
    },
  },
});


// Categorias mais vendidas: dados enviados pelo PHP a partir do banco.
const catCtx = document.getElementById('catChart');
const catLegend = document.getElementById('catLegend');
const dadosCategorias = window.dadosCategorias || [];
const coresCategorias = ['#F5C000', '#a88800', '#5a4800', '#2a2200', '#d99d00', '#776000', '#403600', '#b58e00', '#665200'];
const labelsCategorias = dadosCategorias.map((categoria) => categoria.nome);
const valoresCategorias = dadosCategorias.map((categoria) => categoria.quantidade);
const totalVendido = valoresCategorias.reduce((total, quantidade) => total + quantidade, 0);

if (dadosCategorias.length === 0) {
  catLegend.textContent = 'Nenhuma venda confirmada ainda.';
} else {
  dadosCategorias.forEach((categoria, indice) => {
    const itemLegenda = document.createElement('span');
    itemLegenda.className = 'legend-item';

    const cor = document.createElement('span');
    cor.className = 'leg-sq';
    cor.style.backgroundColor = coresCategorias[indice % coresCategorias.length];

    const percentual = ((categoria.quantidade / totalVendido) * 100).toFixed(1);
    itemLegenda.append(cor, document.createTextNode(` ${categoria.nome} ${percentual}%`));
    catLegend.appendChild(itemLegenda);
  });
}

new Chart(catCtx, {
  type: 'doughnut',
  data: {
    labels: labelsCategorias,
    datasets: [{
      data: valoresCategorias,
      backgroundColor: valoresCategorias.map((_, indice) => coresCategorias[indice % coresCategorias.length]),
      borderWidth: 0,
      hoverOffset: 6,
    }],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '68%',
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#0d0d0c',
        titleColor: '#f5f5f0',
        bodyColor: '#888780',
        borderColor: '#2a2a28',
        borderWidth: 1,
        callbacks: {
          label: (ctx) => {
            const percentual = totalVendido ? ((ctx.parsed / totalVendido) * 100).toFixed(1) : 0;
            return ` ${ctx.label}: ${ctx.parsed} unidade(s) (${percentual}%)`;
          },
        },
      },
    },
  },
});

const tooltipDefaults = {
  backgroundColor: '#0d0d0c',
  titleColor: '#f5f5f0',
  bodyColor: '#888780',
  borderColor: '#2a2a28',
  borderWidth: 1,
};
 
document.getElementById('diaPedidosLabel').textContent = window.diaPedidosHora || '';

new Chart(document.getElementById('trafficChart'), {
  type: 'line',
  data: {
    labels: Array.from({ length: 24 }, (_, h) => String(h).padStart(2, '0') + 'h'),
    datasets: [{
      label: 'Pedidos',
      data: window.pedidosPorHora || [],
      borderColor: '#F5C000',
      backgroundColor: 'rgba(245,192,0,0.06)',
      borderWidth: 2,
      borderDash: [5, 4],
      pointBackgroundColor: '#F5C000',
      pointRadius: 3,
      pointHoverRadius: 5,
      fill: true,
      tension: 0.4,
    }],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { ...tooltipDefaults } },
    scales: {
      x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#666663', font: { size: 11 } } },
      y: {
        grid: { color: 'rgba(255,255,255,0.05)' },
        ticks: { color: '#F5C000', font: { size: 10 }, precision: 0 },
        beginAtZero: true,
      },
    },
  },
});