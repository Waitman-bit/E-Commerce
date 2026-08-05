const dialog = document.getElementById('stock-dialog');
const productId = document.getElementById('dialog-product-id');
const productName = document.getElementById('dialog-product-name');
const currentStock = document.getElementById('dialog-current-stock');
const quantity = document.getElementById('quantity');
const operation = document.getElementById('operation');
const operationButtons = document.querySelectorAll('[data-operation]');

document.querySelectorAll('.adjust-button').forEach((button) => {
  button.addEventListener('click', () => {
    productId.value = button.dataset.id;
    productName.textContent = button.dataset.name;
    currentStock.textContent = button.dataset.stock;
    quantity.value = button.dataset.stock;
    operation.value = 'ajustar';
    operationButtons.forEach((tab) => tab.classList.toggle('active', tab.dataset.operation === 'ajustar'));
    dialog.showModal();
    quantity.focus();
  });
});

operationButtons.forEach((button) => {
  button.addEventListener('click', () => {
    const selectedOperation = button.dataset.operation;
    operation.value = selectedOperation;
    operationButtons.forEach((tab) => tab.classList.toggle('active', tab === button));
    quantity.min = selectedOperation === 'ajustar' ? '0' : '1';
    quantity.value = selectedOperation === 'ajustar' ? currentStock.textContent : '';
    quantity.focus();
  });
});

document.querySelector('.close-dialog').addEventListener('click', () => dialog.close());
document.querySelector('.cancel-dialog').addEventListener('click', () => dialog.close());

dialog.addEventListener('click', (event) => {
  if (event.target === dialog) dialog.close();
});
