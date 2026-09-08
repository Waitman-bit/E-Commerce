// Gráfico da receita mensal
const revenueCtx = document.getElementById('revenueChart');

new Chart(revenueCtx, {
  type: 'bar',
  data: {
    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai'],
    datasets: [
      {
        label: '2026',
        data: [28, 34, 31, 40, 47],
        backgroundColor: '#F5C000',
        borderRadius: 4,
        barPercentage: 0.55,
      },
      {
        label: '2025',
        data: [22, 25, 28, 31, 36],
        backgroundColor: '#3d3300',
        borderRadius: 4,
        barPercentage: 0.55,
      },
    ],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        mode: 'index',
        backgroundColor: '#0d0d0c',
        titleColor: '#f5f5f0',
        bodyColor: '#888780',
        borderColor: '#2a2a28',
        borderWidth: 1,
      },
    },
    scales: {
      x: {
        grid: { display: false },
        ticks: {
          color: '#666663',
          font: { size: 11 },
        },
      },
      y: {
        grid: { color: 'rgba(255,255,255,0.05)' },
        ticks: {
          color: '#666663',
          font: { size: 11 },
          callback: (v) => 'R$' + v + 'k',
        },
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
 
new Chart(document.getElementById('trafficChart'), {
  type: 'line',
  data: {
    labels: ['08h','09h','10h','11h','12h','13h','14h','15h','16h','17h','18h','19h','20h','21h'],
    datasets: [
      {
        label: 'Visitantes',
        data: [45, 98, 140, 175, 210, 270, 320, 295, 260, 230, 185, 140, 90, 55],
        borderColor: '#F5C000',
        backgroundColor: 'rgba(245,192,0,0.06)',
        borderWidth: 2,
        pointBackgroundColor: '#F5C000',
        pointRadius: 3,
        pointHoverRadius: 5,
        fill: true,
        tension: 0.4,
        yAxisID: 'y',
      },
      {
        label: 'Pedidos',
        data: [8, 22, 35, 48, 60, 75, 88, 98, 82, 70, 55, 40, 25, 12],
        borderColor: '#888780',
        borderWidth: 2,
        borderDash: [5, 4],
        pointBackgroundColor: '#888780',
        pointRadius: 3,
        pointHoverRadius: 5,
        fill: false,
        tension: 0.4,
        yAxisID: 'y1',
      },
    ],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { display: false },
      tooltip: { ...tooltipDefaults },
    },
    scales: {
      x: {
        grid: { color: 'rgba(255,255,255,0.04)' },
        ticks: { color: '#666663', font: { size: 11 } },
      },
      y: {
        grid: { color: 'rgba(255,255,255,0.05)' },
        ticks: { color: '#F5C000', font: { size: 10 } },
        beginAtZero: true,
        position: 'left',
        title: { display: true, text: 'visitantes', color: '#666663', font: { size: 10 } },
      },
      y1: {
        grid: { display: false },
        ticks: { color: '#888780', font: { size: 10 } },
        beginAtZero: true,
        position: 'right',
        title: { display: true, text: 'pedidos', color: '#666663', font: { size: 10 } },
      },
    },
  },
});
