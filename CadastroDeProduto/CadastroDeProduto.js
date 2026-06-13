// Preview da foto
document.getElementById('photo-input').addEventListener('change', function (e) {
  const file = e.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = function (ev) {
    const img = document.getElementById('preview-img');
    img.src = ev.target.result;
    img.style.display = 'block';
    document.getElementById('cam-icon').style.display = 'none';
    document.getElementById('photo-label').style.display = 'none';
    document.getElementById('photo-hint').style.display = 'none';
  };
  reader.readAsDataURL(file);
});

// Seleção de gênero (apenas um ativo por vez)
function selectGender(btn, val) {
  document.querySelectorAll('.gender-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  btn.dataset.value = val;
}

// Seleção de tamanho (múltiplos permitidos)
function toggleSize(btn) {
  btn.classList.toggle('active');
}

// Coleta os tamanhos selecionados
function getSizesSelected() {
  return Array.from(document.querySelectorAll('.size-pill.active'))
    .map(btn => btn.textContent.trim());
}

// Coleta o gênero selecionado
function getGenderSelected() {
  const active = document.querySelector('.gender-btn.active');
  return active ? active.dataset.value : null;
}

// Submit
function submitForm() {
  const nome = document.getElementById('nome').value.trim();
  const categoria = document.getElementById('categoria').value;
  const descricao = document.getElementById('descricao').value.trim();
  const tamanhos = getSizesSelected();
  const genero = getGenderSelected();

  if (!nome) {
    alert('Preencha o nome do produto.');
    return;
  }

  if (!categoria) {
    alert('Selecione uma categoria.');
    return;
  }

  const produto = {
    nome,
    categoria,
    descricao,
    tamanhos,
    genero,
  };

  console.log('Produto cadastrado:', produto);
  alert(`Produto "${nome}" cadastrado com sucesso!`);
}