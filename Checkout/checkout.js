document.addEventListener('DOMContentLoaded', function () {
    const campoCep = document.getElementById('cep');
    const campoLogradouro = document.getElementById('logradouro');
    const campoCidade = document.getElementById('cidade');
    const campoEstado = document.getElementById('estado');
    const statusCep = document.getElementById('statusCep');
    const radiosMetodo = document.querySelectorAll('input[name="metodo_entrega"]');
    const detalhesMetodo = document.querySelectorAll('.opcao-detalhe');

    const valorFreteEl = document.getElementById('valorFrete');
    const valorTotalEl = document.getElementById('valorTotal');

    let fretesAtuais = null;

    function formatarPreco(valor) {
        return 'R$ ' + Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function mascararCep(valor) {
        valor = valor.replace(/\D/g, '').slice(0, 8);
        if (valor.length > 5) {
            valor = valor.slice(0, 5) + '-' + valor.slice(5);
        }
        return valor;
    }

    campoCep.addEventListener('input', function () {
        campoCep.value = mascararCep(campoCep.value);
    });

    function atualizarDetalheMetodo(metodo, texto) {
        detalhesMetodo.forEach(function (el) {
            if (el.dataset.metodo === metodo) {
                el.textContent = texto;
            }
        });
    }

    function atualizarResumo() {
        const metodoSelecionado = document.querySelector('input[name="metodo_entrega"]:checked');
        if (!metodoSelecionado || !fretesAtuais) {
            return;
        }
        const info = fretesAtuais[metodoSelecionado.value];
        if (!info) return;

        valorFreteEl.textContent = info.valor > 0 ? formatarPreco(info.valor) : 'Grátis';
        valorTotalEl.textContent = formatarPreco(SUBTOTAL_CARRINHO + Number(info.valor));
    }

    radiosMetodo.forEach(function (radio) {
        radio.addEventListener('change', atualizarResumo);
    });

    async function buscarCep() {
        const cepLimpo = campoCep.value.replace(/\D/g, '');

        if (cepLimpo.length !== 8) {
            return;
        }

        statusCep.textContent = 'Buscando endereço...';
        statusCep.className = 'status-cep carregando';

        try {
            const resposta = await fetch('buscar_cep.php?cep=' + cepLimpo);
            const dados = await resposta.json();

            if (!dados.sucesso) {
                statusCep.textContent = dados.erro || 'Não foi possível buscar o CEP. Preencha manualmente.';
                statusCep.className = 'status-cep erro';
                return;
            }

            if (campoLogradouro.value.trim() === '') {
                campoLogradouro.value = dados.logradouro || '';
            }
            if (campoCidade.value.trim() === '') {
                campoCidade.value = dados.cidade || '';
            }
            if (dados.estado) {
                campoEstado.value = dados.estado;
            }

            statusCep.textContent = 'Endereço encontrado.';
            statusCep.className = 'status-cep ok';

            fretesAtuais = dados.fretes;

            if (fretesAtuais) {
                atualizarDetalheMetodo('normal', formatarPreco(fretesAtuais.normal.valor) + ' - ' + fretesAtuais.normal.prazo);
                atualizarDetalheMetodo('expressa', formatarPreco(fretesAtuais.expressa.valor) + ' - ' + fretesAtuais.expressa.prazo);
                atualizarDetalheMetodo('retirada', 'Grátis - ' + fretesAtuais.retirada.prazo);
                atualizarResumo();
            }
        } catch (erro) {
            statusCep.textContent = 'Erro ao buscar CEP. Preencha o endereço manualmente.';
            statusCep.className = 'status-cep erro';
        }
    }

    campoCep.addEventListener('blur', buscarCep);

    // Se o campo já vier preenchido (perfil do usuário), busca automaticamente.
    if (campoCep.value.replace(/\D/g, '').length === 8) {
        buscarCep();
    }
});
