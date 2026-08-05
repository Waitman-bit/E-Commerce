const imageInput = document.getElementById('imagem');
const previewImage = document.getElementById('preview-image');
if (imageInput && previewImage) {
  imageInput.addEventListener('change', () => {
    const file = imageInput.files[0];
    if (file) previewImage.src = URL.createObjectURL(file);
  });
}

const description = document.getElementById('descricao');
const descriptionCount = document.getElementById('description-count');
if (description && descriptionCount) {
  const updateCount = () => { descriptionCount.textContent = description.value.length; };
  description.addEventListener('input', updateCount);
  updateCount();
}

const search = document.getElementById('product-search');
if (search) {
  search.addEventListener('input', () => {
    const term = search.value.trim().toLowerCase();
    let visible = 0;
    document.querySelectorAll('.product-card').forEach((card) => {
      const matches = card.dataset.name.includes(term);
      card.hidden = !matches;
      if (matches) visible++;
    });
    document.getElementById('empty-search').hidden = visible !== 0;
  });
}
