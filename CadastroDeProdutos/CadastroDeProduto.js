// WAITMAN FRONT END - GERENCIA O UPLOAD E A PREVIA DA FOTO DO PRODUTO
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

// WAITMAN FRONT END - GERENCIA A SELECAO DOS BOTOES DE GENERO
function selectGender(btn, val) {
    document.querySelectorAll('.gender-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    btn.dataset.value = val;
    document.getElementById('genero_oculto').value = val;
}

// WAITMAN FRONT END - ATIVA E DESATIVA AS PILULAS DE TAMANHO AO CLICAR
function toggleSize(btn) {
    btn.classList.toggle('active');
}

// ARIKAWA BACK END - RETORNA UMA LISTA COM TODOS OS TAMANHOS SELECIONADOS
function getSizesSelected() {
    return Array.from(document.querySelectorAll('.size-pill.active'))
        .map(btn => btn.textContent.trim());
}

// WAITMAN FRONT END - RETORNA O GENERO SELECIONADO NO MOMENTO
function getGenderSelected() {
    const active = document.querySelector('.gender-btn.active');
    return active ? active.dataset.value : null;
}

// MORITA BANCOS DE DADOS - CORRIGE O PRECO COLOCANDO CENTAVOS QUANDO CLICA FORA
function formatarPreco(input) {
    let valor = input.value;
    
    valor = valor.replace(',', '.');
    
    if (valor !== "" && !isNaN(valor)) {
        input.value = parseFloat(valor).toFixed(2);
    }
}

// ARIKAWA BACK END - VALIDA OS CAMPOS ANTES DE ENVIAR O FORMULARIO PARA O PHP
function submitForm() {
    const nome = document.getElementById('nome').value.trim();
    const categoria = document.getElementById('categoria').value;
    const descricao = document.getElementById('descricao').value.trim();
    
    let precoRaw = document.getElementById('preco').value.trim();
    precoRaw = precoRaw.replace(',', '.');
    const preco = parseFloat(precoRaw);

    if (!nome) {
        alert('Preencha o nome do produto.');
        return;
    }

    if (!categoria) {
        alert('Selecione uma categoria.');
        return;
    }

    if (!descricao) {
        alert('Preencha a descricao do produto.');
        return;
    }

    if (isNaN(preco) || preco <= 0) {
        alert('Digite um preco valido maior que 0.');
        return;
    }

    document.getElementById('meuFormCadastro').submit();
}

 $('#preco').mask('000.000.000.000.000,00', {reverse: true});
