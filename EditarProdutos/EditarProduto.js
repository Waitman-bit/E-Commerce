const imageInput = document.getElementById('imagem');
const previewImage = document.getElementById('preview-image');
const imagePreview = document.querySelector('.image-preview');

function configurarPreviewDaImagem() {
  if (!imageInput || !previewImage) {
    return;
  }

  if (imagePreview) {
    imagePreview.addEventListener('click', (event) => {
      if (event.target !== imageInput) {
        imageInput.click();
      }
    });
  }

  imageInput.addEventListener('change', () => {
    const file = imageInput.files[0];

    if (file) {
      previewImage.src = URL.createObjectURL(file);
    }
  });
}

function configurarContadorDaDescricao() {
  const description = document.getElementById('descricao');
  const descriptionCount = document.getElementById('description-count');

  if (!description || !descriptionCount) {
    return;
  }

  const atualizarContador = () => {
    descriptionCount.textContent = description.value.length;
  };

  description.addEventListener('input', atualizarContador);
  atualizarContador();
}

function configurarBuscaDeProdutos() {
  const search = document.getElementById('product-search');

  if (!search) {
    return;
  }

  search.addEventListener('input', () => {
    const term = search.value.trim().toLowerCase();
    let visible = 0;

    document.querySelectorAll('.product-card').forEach((card) => {
      const matches = card.dataset.name.includes(term);
      card.hidden = !matches;

      if (matches) {
        visible++;
      }
    });

    document.getElementById('empty-search').hidden = visible !== 0;
  });
}

function configurarMascaraDoPreco() {
  if (typeof $ === 'undefined' || typeof $.fn.mask === 'undefined') {
    return;
  }

  $('#preco').mask('#.##0,00', {
    reverse: true,
    placeholder: '0,00'
  });
}

configurarPreviewDaImagem();
configurarContadorDaDescricao();
configurarBuscaDeProdutos();
configurarMascaraDoPreco();