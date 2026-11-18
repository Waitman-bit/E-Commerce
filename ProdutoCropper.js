document.addEventListener('DOMContentLoaded', () => {
  const input = document.querySelector('#photo-input, #imagem');
  const form = input?.form;
  const preview = document.querySelector('#preview-img, #preview-image');
  if (!input || !form || !preview) return;
  const originalPreviewSrc = preview.src;
  const originalPreviewDisplay = preview.style.display;

  const styles = document.createElement('style');
  styles.textContent = `
    .crop-overlay{position:fixed;inset:0;z-index:99999;display:none;place-items:center;padding:20px;background:#000c;font-family:Arial,sans-serif;color:#f5f5f5}
    .crop-overlay.open{display:grid}.crop-dialog{width:min(460px,100%);padding:22px;background:#171717;border:1px solid #444;border-radius:14px;box-shadow:0 20px 70px #000}
    .crop-dialog h2{margin:0 0 7px;font-size:20px}.crop-dialog p{margin:0 0 16px;color:#aaa;font-size:13px}
    .crop-canvas-wrap{width:min( min(72vw, 360px), 100%);aspect-ratio:1;margin:0 auto 16px;overflow:hidden;background:#fff;border-radius:8px;touch-action:none}
    .crop-canvas{display:block;width:100%;height:100%;cursor:grab;touch-action:none}.crop-canvas.dragging{cursor:grabbing}
    .crop-zoom{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:12px;font-size:13px}.crop-zoom input{width:100%;accent-color:#f5c400}
    .crop-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}.crop-actions button{padding:10px 14px;border:0;border-radius:7px;font-weight:700;cursor:pointer}
    .crop-cancel{background:#333;color:#eee}.crop-confirm{background:#f5c400;color:#111}
  `;
  document.head.appendChild(styles);

  const overlay = document.createElement('div');
  overlay.className = 'crop-overlay';
  overlay.innerHTML = `<section class="crop-dialog" role="dialog" aria-modal="true" aria-labelledby="crop-title">
    <h2 id="crop-title">Enquadre a imagem do card</h2><p>Arraste para posicionar e use o controle para aproximar ou afastar.</p>
    <div class="crop-canvas-wrap"><canvas class="crop-canvas" width="600" height="600"></canvas></div>
    <label class="crop-zoom">Zoom <input class="crop-range" type="range" min="0.4" max="3" step="0.01" value="1"><span>+</span></label>
    <div class="crop-actions"><button type="button" class="crop-cancel">Cancelar</button><button type="button" class="crop-confirm">Usar imagem</button></div>
  </section>`;
  document.body.appendChild(overlay);

  const canvas = overlay.querySelector('canvas');
  const ctx = canvas.getContext('2d');
  const zoomInput = overlay.querySelector('.crop-range');
  const size = canvas.width;
  let source = null;
  let sourceUrl = null;
  let scale = 1;
  let zoom = 1;
  let offsetX = 0;
  let offsetY = 0;
  let drag = null;
  let preparedSubmit = false;

  function draw() {
    if (!source) return;
    scale = Math.max(size / source.naturalWidth, size / source.naturalHeight) * zoom;
    const width = source.naturalWidth * scale;
    const height = source.naturalHeight * scale;
    offsetX = width <= size ? (size - width) / 2 : Math.min(0, Math.max(size - width, offsetX));
    offsetY = height <= size ? (size - height) / 2 : Math.min(0, Math.max(size - height, offsetY));
    ctx.clearRect(0, 0, size, size);
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, size, size);
    ctx.drawImage(source, offsetX, offsetY, width, height);
  }

  input.addEventListener('change', () => {
    const file = input.files?.[0];
    if (!file) return;
    if (sourceUrl) URL.revokeObjectURL(sourceUrl);
    sourceUrl = URL.createObjectURL(file);
    source = new Image();
    source.onload = () => {
      zoom = 1;
      zoomInput.value = '1';
      offsetX = (size - source.naturalWidth * Math.max(size / source.naturalWidth, size / source.naturalHeight)) / 2;
      offsetY = (size - source.naturalHeight * Math.max(size / source.naturalWidth, size / source.naturalHeight)) / 2;
      draw();
      overlay.classList.add('open');
    };
    source.src = sourceUrl;
  });

  zoomInput.addEventListener('input', () => { zoom = Number(zoomInput.value); draw(); });
  canvas.addEventListener('pointerdown', (event) => {
    drag = { x: event.clientX, y: event.clientY, offsetX, offsetY };
    canvas.classList.add('dragging');
    canvas.setPointerCapture(event.pointerId);
  });
  canvas.addEventListener('pointermove', (event) => {
    if (!drag) return;
    const bounds = canvas.getBoundingClientRect();
    offsetX = drag.offsetX + (event.clientX - drag.x) * size / bounds.width;
    offsetY = drag.offsetY + (event.clientY - drag.y) * size / bounds.height;
    draw();
  });
  const stopDragging = () => { drag = null; canvas.classList.remove('dragging'); };
  canvas.addEventListener('pointerup', stopDragging);
  canvas.addEventListener('pointercancel', stopDragging);

  overlay.querySelector('.crop-cancel').addEventListener('click', () => {
    overlay.classList.remove('open');
    input.value = '';
    preview.src = originalPreviewSrc;
    preview.style.display = originalPreviewDisplay;
    if (input.id === 'photo-input') {
      preview.style.display = 'none';
      ['cam-icon', 'photo-label', 'photo-hint'].forEach((id) => {
        const element = document.getElementById(id);
        if (element) element.style.display = '';
      });
    }
    source = null;
  });

  overlay.querySelector('.crop-confirm').addEventListener('click', () => {
    canvas.toBlob((blob) => {
      if (!blob) return;
      const croppedFile = new File([blob], 'produto-enquadrado.jpg', { type: 'image/jpeg' });
      const transfer = new DataTransfer();
      transfer.items.add(croppedFile);
      input.files = transfer.files;
      preview.src = canvas.toDataURL('image/jpeg', 0.92);
      preview.style.display = 'block';
      overlay.classList.remove('open');
      source = null;
    }, 'image/jpeg', 0.92);
  });

  form.addEventListener('submit', (event) => {
    if (preparedSubmit) return;
    if (overlay.classList.contains('open')) {
      event.preventDefault();
      return;
    }
    preparedSubmit = true;
    event.preventDefault();
    form.requestSubmit(event.submitter || undefined);
  });
});
