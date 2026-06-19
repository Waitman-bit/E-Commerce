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

function selectGender(btn, val) {
    document.querySelectorAll('.gender-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    btn.dataset.value = val;
    document.getElementById('genero_oculto').value = val;
}

function toggleSize(btn) {
    btn.classList.toggle('active');
}

function getSizesSelected() {
    return Array.from(document.querySelectorAll('.size-pill.active'))
        .map(btn => btn.textContent.trim());
}

function getGenderSelected() {
    const active = document.querySelector('.gender-btn.active');
    return active ? active.dataset.value : null;
}

function submitForm() {
    const nome = document.getElementById('nome').value.trim();
    const categoria = document.getElementById('categoria').value;
    const descricao = document.getElementById('descricao').value.trim();
    const preco = document.getElementById('preco').value.trim();

    if (!nome) {
        alert('Preencha o nome do produto.');
        return;
    }

    if (!categoria) {
        alert('Selecione uma categoria.');
        return;
    }

    if (!descricao) {
        alert('Preencha a descrição do produto.');
        return;
    }

    if (!preco || parseFloat(preco) <= 0) {
        alert('Digite um preço válido (maior que 0).');
        return;
    }

    document.getElementById('meuFormCadastro').submit();
}