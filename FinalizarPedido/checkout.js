/**
 * checkout.js
 *
 * Responsável por: máscaras de campo, busca de endereço via ViaCEP,
 * cálculo de frete (via calcular_frete.php), validação em tempo real,
 * alternância de forma de pagamento, cupom simulado e atualização do resumo.
 */

document.addEventListener('DOMContentLoaded', function () {

    const cepInput = document.getElementById('cep');
    const cpfInput = document.getElementById('cpf');
    const telefoneInput = document.getElementById('telefone');
    const numeroCartaoInput = document.getElementById('numero_cartao');
    const validadeInput = document.getElementById('validade_cartao');
    const cvvInput = document.getElementById('cvv');

    const subtotal = parseFloat(document.getElementById('input-subtotal').value) || 0;

    let freteAtual = { valor: null, prazo: null, regiao: null };
    let descontoAtual = 0;

    /* =====================================================
       MÁSCARAS
       ===================================================== */
    function aplicarMascara(input, formatador) {
        if (!input) return;
        input.addEventListener('input', function () {
            input.value = formatador(input.value);
        });
    }

    aplicarMascara(cepInput, v => v.replace(/\D/g, '').slice(0, 8)
        .replace(/^(\d{5})(\d)/, '$1-$2'));

    aplicarMascara(cpfInput, v => v.replace(/\D/g, '').slice(0, 11)
        .replace(/^(\d{3})(\d)/, '$1.$2')
        .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1-$2'));

    aplicarMascara(telefoneInput, v => v.replace(/\D/g, '').slice(0, 11)
        .replace(/^(\d{2})(\d)/, '($1) $2')
        .replace(/(\d{5})(\d)/, '$1-$2'));

    aplicarMascara(numeroCartaoInput, v => v.replace(/\D/g, '').slice(0, 19)
        .replace(/(\d{4})(?=\d)/g, '$1 '));

    aplicarMascara(validadeInput, v => v.replace(/\D/g, '').slice(0, 4)
        .replace(/^(\d{2})(\d)/, '$1/$2'));

    aplicarMascara(cvvInput, v => v.replace(/\D/g, '').slice(0, 4));

    /* =====================================================
       VALIDAÇÃO
       ===================================================== */
    function validarCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;

        let soma = 0;
        for (let i = 0; i < 9; i++) soma += parseInt(cpf[i], 10) * (10 - i);
        let resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf[9], 10)) return false;

        soma = 0;
        for (let i = 0; i < 10; i++) soma += parseInt(cpf[i], 10) * (11 - i);
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        return resto === parseInt(cpf[10], 10);
    }

    function mostrarErro(campoId, mensagem) {
        const span = document.getElementById('erro-' + campoId);
        if (span) span.textContent = mensagem;
        const input = document.getElementById(campoId);
        if (input) input.classList.toggle('campo-invalido', !!mensagem);
    }

    function validarCampo(campoId) {
        const input = document.getElementById(campoId);
        if (!input) return true;
        const valor = input.value.trim();

        switch (campoId) {
            case 'nome_completo':
                if (valor.length < 3) { mostrarErro(campoId, 'Informe o nome completo.'); return false; }
                break;
            case 'cpf':
                if (!validarCPF(valor)) { mostrarErro(campoId, 'CPF inválido.'); return false; }
                break;
            case 'email':
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor)) { mostrarErro(campoId, 'Email inválido.'); return false; }
                break;
            case 'telefone':
                if (valor.replace(/\D/g, '').length < 10) { mostrarErro(campoId, 'Telefone inválido.'); return false; }
                break;
            case 'cep':
                if (valor.replace(/\D/g, '').length !== 8) { mostrarErro(campoId, 'CEP inválido.'); return false; }
                break;
            case 'rua':
            case 'numero':
            case 'bairro':
            case 'cidade':
            case 'estado':
                if (valor === '') { mostrarErro(campoId, 'Campo obrigatório.'); return false; }
                break;
            case 'numero_cartao':
                if (valor.replace(/\D/g, '').length < 13) { mostrarErro(campoId, 'Número de cartão inválido.'); return false; }
                break;
            case 'nome_cartao':
                if (valor === '') { mostrarErro(campoId, 'Informe o nome impresso no cartão.'); return false; }
                break;
            case 'validade_cartao':
                if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(valor)) { mostrarErro(campoId, 'Validade inválida (MM/AA).'); return false; }
                break;
            case 'cvv':
                if (!/^\d{3,4}$/.test(valor)) { mostrarErro(campoId, 'CVV inválido.'); return false; }
                break;
        }

        mostrarErro(campoId, '');
        return true;
    }

    ['nome_completo', 'cpf', 'email', 'telefone', 'cep', 'rua', 'numero', 'bairro', 'cidade', 'estado',
     'numero_cartao', 'nome_cartao', 'validade_cartao', 'cvv'].forEach(id => {
        const input = document.getElementById(id);
        if (input) input.addEventListener('blur', () => validarCampo(id));
    });

    /* =====================================================
       BUSCA DE ENDEREÇO (ViaCEP)
       ===================================================== */
    cepInput.addEventListener('blur', function () {
        const cep = cepInput.value.replace(/\D/g, '');
        const statusEl = document.getElementById('cep-status');

        if (cep.length !== 8) return;

        statusEl.textContent = 'Buscando endereço...';

        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(res => res.json())
            .then(data => {
                if (data.erro) {
                    statusEl.textContent = 'CEP não encontrado.';
                    return;
                }
                document.getElementById('rua').value = data.logradouro || '';
                document.getElementById('bairro').value = data.bairro || '';
                document.getElementById('cidade').value = data.localidade || '';
                document.getElementById('estado').value = data.uf || '';
                statusEl.textContent = 'Endereço encontrado.';
                document.getElementById('numero').focus();

                atualizarFrete();
            })
            .catch(() => { statusEl.textContent = 'Não foi possível buscar o CEP.'; });
    });

    /* =====================================================
       CÁLCULO DE FRETE
       ===================================================== */
    function tipoEntregaSelecionado() {
        const el = document.querySelector('input[name="tipo_entrega"]:checked');
        return el ? el.value : 'padrao';
    }

    function atualizarFrete() {
        const tipo = tipoEntregaSelecionado();

        if (tipo === 'retirada') {
            freteAtual = { valor: 0, prazo: 'Disponível em até 24 horas úteis', regiao: 'retirada' };
            preencherFreteNaTela();
            return;
        }

        const cep = cepInput.value.replace(/\D/g, '');
        if (cep.length !== 8) return;

        fetch(`calcular_frete.php?cep=${cep}&tipo=${tipo}`)
            .then(res => res.json())
            .then(data => {
                if (data.erro) {
                    mostrarErro('cep', data.erro);
                    return;
                }
                freteAtual = data;
                preencherFreteNaTela();
                atualizarTodasOpcoesEntrega(cep);
            })
            .catch(() => { mostrarErro('cep', 'Não foi possível calcular o frete.'); });
    }

    function atualizarTodasOpcoesEntrega(cep) {
        ['padrao', 'expressa'].forEach(tipo => {
            fetch(`calcular_frete.php?cep=${cep}&tipo=${tipo}`)
                .then(res => res.json())
                .then(data => {
                    if (data.erro) return;
                    const prazoLabel = document.querySelector(`.opcao-entrega[data-tipo="${tipo}"] .opcao-prazo`);
                    const valorLabel = document.querySelector(`.opcao-entrega[data-tipo="${tipo}"] .opcao-valor`);
                    if (prazoLabel) prazoLabel.textContent = data.prazo;
                    if (valorLabel) valorLabel.textContent = formatarMoeda(data.valor);
                });
        });
    }

    function preencherFreteNaTela() {
        document.getElementById('frete_valor').value = freteAtual.valor ?? 0;
        document.getElementById('frete_prazo').value = freteAtual.prazo ?? '';
        document.getElementById('frete_regiao').value = freteAtual.regiao ?? '';

        const tipo = tipoEntregaSelecionado();
        const prazoLabel = document.querySelector(`.opcao-entrega[data-tipo="${tipo}"] .opcao-prazo`);
        const valorLabel = document.querySelector(`.opcao-entrega[data-tipo="${tipo}"] .opcao-valor`);

        if (prazoLabel && freteAtual.prazo) prazoLabel.textContent = freteAtual.prazo;
        if (valorLabel && freteAtual.valor !== null) {
            valorLabel.textContent = freteAtual.valor === 0 ? 'Grátis' : formatarMoeda(freteAtual.valor);
        }

        atualizarResumo();
    }

    document.querySelectorAll('input[name="tipo_entrega"]').forEach(radio => {
        radio.addEventListener('change', atualizarFrete);
    });

    // Se o CEP já vier preenchido (usuário logado com CEP salvo), calcula automaticamente
    if (cepInput.value.replace(/\D/g, '').length === 8) {
        atualizarFrete();
        atualizarTodasOpcoesEntrega(cepInput.value.replace(/\D/g, ''));
    }

    /* =====================================================
       FORMA DE PAGAMENTO
       ===================================================== */
    const paineis = {
        cartao_credito: document.getElementById('painel-cartao'),
        cartao_debito: document.getElementById('painel-cartao'),
        pix: document.getElementById('painel-pix'),
        boleto: document.getElementById('painel-boleto'),
    };

    function alternarPainelPagamento() {
        const tipoChecked = document.querySelector('input[name="forma_pagamento"]:checked');
        if (!tipoChecked) return;
        const tipo = tipoChecked.value;

        Object.values(paineis).forEach(p => { if (p) p.style.display = 'none'; });

        if (paineis[tipo]) {
            paineis[tipo].style.display = 'block';
            paineis[tipo].classList.remove('painel-anim');
            void paineis[tipo].offsetWidth; // reinicia a animação
            paineis[tipo].classList.add('painel-anim');
        }

        const grupoParcelas = document.getElementById('grupo-parcelas');
        if (grupoParcelas) grupoParcelas.style.display = tipo === 'cartao_credito' ? 'block' : 'none';

        if (tipo === 'boleto') atualizarBoleto();
    }

    document.querySelectorAll('input[name="forma_pagamento"]').forEach(radio => {
        radio.addEventListener('change', alternarPainelPagamento);
    });
    alternarPainelPagamento();

    function atualizarBoleto() {
        const total = calcularTotal();
        document.getElementById('boleto-valor').textContent = formatarMoeda(total);
        const vencimento = new Date();
        vencimento.setDate(vencimento.getDate() + 3);
        document.getElementById('boleto-vencimento').textContent = vencimento.toLocaleDateString('pt-BR');
    }

    document.getElementById('btn-gerar-boleto')?.addEventListener('click', atualizarBoleto);

    document.getElementById('btn-copiar-pix')?.addEventListener('click', function () {
        const campo = document.getElementById('pix_codigo');
        campo.select();
        navigator.clipboard.writeText(campo.value);
        const textoOriginal = this.textContent;
        this.textContent = 'Copiado!';
        setTimeout(() => { this.textContent = textoOriginal; }, 2000);
    });

    /* =====================================================
       PARCELAS
       ===================================================== */
    function atualizarParcelas() {
        const select = document.getElementById('parcelas');
        if (!select) return;

        const total = calcularTotal();
        const valorAtualSelecionado = select.value || '1';
        select.innerHTML = '';

        for (let i = 1; i <= 12; i++) {
            const valorParcela = total / i;
            const option = document.createElement('option');
            option.value = i;
            option.textContent = `${i}x de ${formatarMoeda(valorParcela)}` + (i === 1 ? ' à vista' : ' sem juros');
            select.appendChild(option);
        }

        select.value = valorAtualSelecionado;
    }

    /* =====================================================
       CUPOM (simulado - não existe tabela "cupom" no banco)
       ===================================================== */
    const cuponsSimulados = {
        'TITAN10': { tipo: 'percentual', valor: 0.10 },
        'FRETEGRATIS': { tipo: 'frete_gratis' },
    };

    document.getElementById('btn-aplicar-cupom').addEventListener('click', function () {
        const codigo = document.getElementById('cupom_codigo').value.trim().toUpperCase();
        const erroCupom = document.getElementById('erro-cupom');
        const cupom = cuponsSimulados[codigo];

        if (!codigo) {
            erroCupom.textContent = 'Informe um código de cupom.';
            return;
        }

        if (!cupom) {
            erroCupom.textContent = 'Cupom inválido.';
            descontoAtual = 0;
            document.getElementById('linha-desconto').style.display = 'none';
            document.getElementById('input-cupom-aplicado').value = '';
            document.getElementById('input-desconto').value = '0';
            atualizarResumo();
            return;
        }

        erroCupom.textContent = '';

        if (cupom.tipo === 'percentual') {
            descontoAtual = subtotal * cupom.valor;
        } else if (cupom.tipo === 'frete_gratis') {
            freteAtual.valor = 0;
            document.getElementById('frete_valor').value = 0;
            descontoAtual = 0;
        }

        document.getElementById('input-cupom-aplicado').value = codigo;
        document.getElementById('input-desconto').value = descontoAtual.toFixed(2);
        document.getElementById('linha-desconto').style.display = descontoAtual > 0 ? 'flex' : 'none';
        atualizarResumo();
    });

    /* =====================================================
       RESUMO DO PEDIDO
       ===================================================== */
    function formatarMoeda(valor) {
        return 'R$ ' + Number(valor).toFixed(2).replace('.', ',');
    }

    function calcularTotal() {
        const frete = freteAtual.valor ?? 0;
        return Math.max(subtotal + frete - descontoAtual, 0);
    }

    function atualizarResumo() {
        const frete = freteAtual.valor;
        document.getElementById('resumo-frete').textContent =
            (frete === null || frete === undefined) ? 'Informe o CEP' : formatarMoeda(frete);
        document.getElementById('resumo-desconto').textContent = '- ' + formatarMoeda(descontoAtual);
        document.getElementById('resumo-total').textContent = formatarMoeda(calcularTotal());
        atualizarParcelas();
    }

    atualizarResumo();

    /* =====================================================
       VALIDAÇÃO FINAL NO ENVIO
       ===================================================== */
    document.getElementById('form-checkout').addEventListener('submit', function (e) {
        const camposObrigatorios = ['nome_completo', 'cpf', 'email', 'telefone', 'cep', 'rua', 'numero', 'bairro', 'cidade', 'estado'];
        let valido = true;

        camposObrigatorios.forEach(id => { if (!validarCampo(id)) valido = false; });

        const formaPagamento = document.querySelector('input[name="forma_pagamento"]:checked').value;
        if (formaPagamento === 'cartao_credito' || formaPagamento === 'cartao_debito') {
            ['numero_cartao', 'nome_cartao', 'validade_cartao', 'cvv'].forEach(id => {
                if (!validarCampo(id)) valido = false;
            });
        }

        if (freteAtual.valor === null || freteAtual.valor === undefined) {
            mostrarErro('cep', 'Informe um CEP válido para calcular o frete.');
            valido = false;
        }

        if (!valido) {
            e.preventDefault();
            const primeiroErro = document.querySelector('.campo-invalido');
            if (primeiroErro) primeiroErro.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
});
