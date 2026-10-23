const dialog = document.getElementById('stock-dialog');
const productId = document.getElementById('dialog-product-id');
const productName = document.getElementById('dialog-product-name');
const currentStock = document.getElementById('dialog-current-stock');
const sizeStockList = document.getElementById('size-stock-list');
const sizeSelect = document.getElementById('add-size-select');
const addSizeButton = document.getElementById('add-size-button');

function updateTotal() {
  const total = Array.from(sizeStockList.querySelectorAll('input[type="number"]'))
    .reduce((sum, input) => sum + (Number(input.value) || 0), 0);
  currentStock.textContent = String(total);
}

function addSizeRow(size, isNew = false) {
  if (sizeStockList.querySelector(`[data-size-id="${size.id}"]`)) return;

  const row = document.createElement('div');
  row.className = 'size-stock-row';
  row.dataset.sizeId = String(size.id);

  const name = document.createElement('span');
  name.className = 'size-stock-name';
  name.textContent = size.descricao;

  const label = document.createElement('label');
  label.className = 'size-stock-field';
  label.textContent = 'Unidades';

  const input = document.createElement('input');
  input.type = 'number';
  input.name = `estoques[${size.id}]`;
  input.min = '0';
  input.step = '1';
  input.required = true;
  input.inputMode = 'numeric';
  input.value = String(Number(size.estoque) || 0);
  input.addEventListener('input', updateTotal);
  label.appendChild(input);
  const controls = document.createElement('div');
  controls.className = 'size-stock-controls';
  controls.appendChild(label);

  const removeButton = document.createElement('button');
  removeButton.type = 'button';
  removeButton.className = 'remove-size-button';
  removeButton.setAttribute('aria-label', `Remover tamanho ${size.descricao}`);
  removeButton.title = 'Remover tamanho';
  removeButton.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m4 4v6m6-6v6"/></svg>';
  removeButton.textContent = '🗑';
  removeButton.addEventListener('click', () => {
    row.remove();
    const option = Array.from(sizeSelect.options).find((item) => item.value === String(size.id));
    if (option) option.disabled = false;
    updateTotal();
  });
  controls.appendChild(removeButton);
  row.append(name, controls);

  if (isNew) {
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'novos_tamanhos[]';
    hidden.value = String(size.id);
    row.appendChild(hidden);
  }

  sizeStockList.appendChild(row);
  const option = Array.from(sizeSelect.options).find((item) => item.value === String(size.id));
  if (option) option.disabled = true;
  updateTotal();
}

document.querySelectorAll('.adjust-button').forEach((button) => {
  button.addEventListener('click', () => {
    productId.value = button.dataset.id;
    productName.textContent = button.dataset.name;
    sizeStockList.replaceChildren();
    Array.from(sizeSelect.options).forEach((option) => { option.disabled = false; });

    let sizes = [];
    try { sizes = JSON.parse(button.dataset.sizes || '[]'); } catch (_) { sizes = []; }
    sizes.forEach((size) => addSizeRow(size));
    updateTotal();
    dialog.showModal();
    sizeStockList.querySelector('input')?.focus();
  });
});

addSizeButton.addEventListener('click', () => {
  const id = Number(sizeSelect.value);
  if (!id) return;
  const option = sizeSelect.options[sizeSelect.selectedIndex];
  addSizeRow({ id, descricao: option.textContent, estoque: 0 }, true);
  sizeSelect.value = '';
});

document.querySelector('.close-dialog').addEventListener('click', () => dialog.close());
document.querySelector('.cancel-dialog').addEventListener('click', () => dialog.close());
dialog.addEventListener('click', (event) => {
  if (event.target === dialog) dialog.close();
});

// Remove os controles antigos de ajuste total, mantidos apenas para compatibilidade visual.
document.querySelector('.operation-tabs')?.remove();
