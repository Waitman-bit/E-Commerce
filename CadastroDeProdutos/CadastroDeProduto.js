// WAITMAN FRONT END - GERENCIA O UPLOAD E A PREVIA DA FOTO DO PRODUTO
document.addEventListener('DOMContentLoaded', function () {
    const photoInput = document.getElementById('photo-input');

    if (photoInput) {
        photoInput.addEventListener('change', function (event) {
            const file = event.target.files && event.target.files[0];

            if (!file) {
                return;
            }

            const reader = new FileReader();

            reader.onload = function (loadEvent) {
                const img = document.getElementById('preview-img');

                if (img) {
                    img.src = loadEvent.target.result;
                    img.style.display = 'block';
                }

                const camIcon = document.getElementById('cam-icon');
                const photoLabel = document.getElementById('photo-label');
                const photoHint = document.getElementById('photo-hint');

                if (camIcon) camIcon.style.display = 'none';
                if (photoLabel) photoLabel.style.display = 'none';
                if (photoHint) photoHint.style.display = 'none';
            };

            reader.readAsDataURL(file);
        });
    }
});

// WAITMAN FRONT END - GERENCIA A SELEÇÃO DOS BOTÕES DE GÊNERO
function selectGender(btn, val) {
    const generoOculto = document.getElementById('genero_oculto');
    const isAlreadySelected = btn.classList.contains('active') && (!generoOculto || generoOculto.value === val);

    document.querySelectorAll('.gender-btn').forEach(function (b) {
        b.classList.remove('active');
        delete b.dataset.value;
    });

    if (isAlreadySelected) {
        if (generoOculto) {
            generoOculto.value = '';
        }
        return;
    }

    btn.classList.add('active');
    btn.dataset.value = val;

    if (generoOculto) {
        generoOculto.value = val;
    }
}

// WAITMAN FRONT END - ATIVA E DESATIVA AS PILULAS DE TAMANHO
function toggleSize(btn) {
    const unicoBtn = document.querySelector('.size-pill.unico');
    if (unicoBtn && unicoBtn.classList.contains('active')) {
        unicoBtn.classList.remove('active');
    }
    btn.classList.toggle('active');
    atualizarEstoqueTamanhos();
}

function toggleUniqueSize(btn) {
    const outrosTamanhos = document.querySelectorAll('.size-pill:not(.unico)');
    outrosTamanhos.forEach(function (b) {
        b.classList.remove('active');
    });
    btn.classList.toggle('active');
    atualizarEstoqueTamanhos();
}

// CRIA OS CAMPOS DE ESTOQUE PARA OS TAMANHOS SELECIONADOS
function atualizarEstoqueTamanhos() {
    const container = document.getElementById('estoque-tamanhos');

    if (!container) {
        return;
    }

    const tamanhosSelecionados = document.querySelectorAll('.size-pill.active');
    const estoquesAtuais = {};

    document.querySelectorAll('.estoque-input').forEach(function (input) {
        estoquesAtuais[input.dataset.tamanho] = input.value;
    });

    container.innerHTML = '';

    tamanhosSelecionados.forEach(function (btn) {
        const tamanho = btn.dataset.size;
        const estoque = Object.prototype.hasOwnProperty.call(estoquesAtuais, tamanho)
            ? estoquesAtuais[tamanho]
            : '0';

        const linha = document.createElement('div');
        linha.classList.add('estoque-tamanho-item');

        const minValue = tamanho === 'Único' ? 1 : 0;

        linha.innerHTML = `
            <span class="estoque-tamanho-nome">Tamanho Selecionado: ${tamanho}</span>

            <label>
                Estoque
                <input
                    type="number"
                    class="estoque-input"
                    data-tamanho="${tamanho}"
                    min="${minValue}"
                    value="${estoque}"
                >
            </label>
        `;

        container.appendChild(linha);
    });
}

// ARIKAWA BACK END - RETORNA UMA LISTA COM TODOS OS TAMANHOS SELECIONADOS
function getSizesSelected() {
    return Array.from(document.querySelectorAll('.size-pill.active')).map(function (btn) {
        return btn.dataset.size;
    });
}

// WAITMAN FRONT END - RETORNA O GÊNERO SELECIONADO
function getGenderSelected() {
    const active = document.querySelector('.gender-btn.active');
    const generoOculto = document.getElementById('genero_oculto');

    if (!active) {
        if (generoOculto) {
            generoOculto.value = '';
        }
        return null;
    }

    return active.dataset.value || generoOculto?.value || null;
}

// MONTA OS TAMANHOS E ESTOQUES PARA ENVIAR AO PHP
function prepararTamanhos() {
    const tamanhos = [];

    document.querySelectorAll('.estoque-input').forEach(function (input) {
        const tamanho = input.dataset.tamanho;
        const estoque = parseInt(input.value, 10);

        tamanhos.push({
            tamanho: tamanho,
            estoque: estoque
        });
    });

    const campoTamanhos = document.getElementById('tamanhos_php');
    if (campoTamanhos) {
        campoTamanhos.value = JSON.stringify(tamanhos);
    }
}

// MORITA BANCOS DE DADOS - NORMALIZA E FORMATA O PREÇO
function formatarPreco(input) {
    let valor = (input.value || '').trim();
    valor = valor.replace(/\./g, '').replace(',', '.');

    if (valor !== '' && !isNaN(valor)) {
        const numero = parseFloat(valor);
        input.value = numero.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
}

// ARIKAWA BACK END - VALIDA OS CAMPOS ANTES DE ENVIAR O FORMULÁRIO
function submitForm() {
    const nome = document.getElementById('nome').value.trim();
    const categoria = document.getElementById('categoria').value;
    const descricao = document.getElementById('descricao').value.trim();
    let precoRaw = document.getElementById('preco').value.trim();

    precoRaw = precoRaw.replace(/\./g, '').replace(',', '.');
    const preco = parseFloat(precoRaw);

    if (!nome) {
        alert('Preencha o nome do produto.');
        return false;
    }

    if (!categoria) {
        alert('Selecione uma categoria.');
        return false;
    }

    if (!descricao) {
        alert('Preencha a descrição do produto.');
        return false;
    }

    if (isNaN(preco) || preco <= 0) {
        alert('Digite um preço válido maior que 0.');
        return false;
    }

    const estoques = document.querySelectorAll('.estoque-input');

    if (estoques.length > 0) {
        for (const input of estoques) {
            const estoque = parseInt(input.value, 10);

            if (isNaN(estoque) || estoque <= 0) {
                alert('O estoque do tamanho ' + input.dataset.tamanho + ' deve ser maior que 0.');
                input.focus();
                return false;
            }
        }
    }

    prepararTamanhos();
    return true;
}

// MORITA BANCOS DE DADOS - MASCARA DO PREÇO
if (typeof $ !== 'undefined') {
    $('#preco').mask('#.##0,00', {
        reverse: true,
        placeholder: '0,00'
    });
}

// Descrição com IA - Gemini 
async function gerarDescricaoComIA() {
    const btn = document.getElementById('btn-gerar-ia');
    const statusEl = document.getElementById('status-ia');
    const descricaoEl = document.getElementById('descricao');

    const nome = document.getElementById('nome')?.value.trim() || '';
    const selectCategoria = document.getElementById('categoria');
    const categoriaTexto = selectCategoria && selectCategoria.selectedIndex > 0
        ? selectCategoria.options[selectCategoria.selectedIndex].text.trim()
        : '';

    const genero = typeof getGenderSelected === 'function' ? (getGenderSelected() || '') : '';
    const tamanhos = typeof getSizesSelected === 'function' ? getSizesSelected().join(', ') : '';
    const preco = document.getElementById('preco')?.value.trim() || '';
    const detalhes = descricaoEl ? descricaoEl.value.trim() : '';

    if (!nome || !categoria || !descricao) {
        alert('Preencha os dados do produto antes de gerar a descrição com IA.');
        document.getElementById('nome')?.focus();
        return;
    }

    if (btn) {
        btn.disabled = true;
        btn.classList.add('is-loading');
        btn.dataset.textoOriginal = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-sparkles" aria-hidden="true"></i><span>Gerando com Gemini...</span>';
    }

    if (statusEl) {
        statusEl.style.color = '#4e4c4c';
        statusEl.textContent = 'Gerando descrição curta com IA...';
    }

    try {
        const resposta = await fetch('gerar_descricao.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                nome: nome,
                categoria: categoriaTexto,
                genero: genero,
                tamanhos: tamanhos,
                preco: preco,
                detalhes: detalhes
            })
        });

        const textoBruto = await resposta.text();
        let dados;

        try {
            dados = JSON.parse(textoBruto);
        } catch (e) {
            throw new Error('Resposta inválida do servidor ao consultar a IA.');
        }

        if (!resposta.ok || dados.erro) {
            throw new Error(dados.erro || 'Erro ao gerar descrição com IA.');
        }

        const descricaoFinal = String(dados.descricao || '').slice(0, 150);

        if (descricaoEl) {
            descricaoEl.value = descricaoFinal;
        }

        if (statusEl) {
            statusEl.style.color = '#15803d';
            statusEl.textContent = `Descrição gerada (${descricaoFinal.length}/150 caracteres).`;
        }
    } catch (erro) {
        const msgErro = erro && erro.message
            ? erro.message
            : 'Não foi possível conectar à IA no momento. Tente novamente.';

        console.error('Erro IA:', erro);

        if (statusEl) {
            statusEl.style.color = '#b91c1c';
            statusEl.textContent = msgErro;
        }

        alert('Atenção: ' + msgErro);
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('is-loading');
            if (btn.dataset.textoOriginal) {
                btn.innerHTML = btn.dataset.textoOriginal;
            }
        }
    }
}