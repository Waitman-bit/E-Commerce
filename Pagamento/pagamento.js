document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('input[name="forma_pagamento"]');
    const paineis = {
        credito: document.getElementById('painelCredito'),
        debito: document.getElementById('painelDebito'),
        pix: document.getElementById('painelPix'),
    };

    function alternarPainel() {
        const selecionado = document.querySelector('input[name="forma_pagamento"]:checked').value;
        Object.keys(paineis).forEach(function (chave) {
            paineis[chave].style.display = chave === selecionado ? 'block' : 'none';
        });
    }

    radios.forEach(function (radio) {
        radio.addEventListener('change', alternarPainel);
    });

    // Máscara simples do número de cartão (apenas visual, este campo
    // não possui atributo "name" e por isso nunca é enviado ao servidor).
    const numeroCartao = document.getElementById('numeroCartao');
    if (numeroCartao) {
        numeroCartao.addEventListener('input', function () {
            let valor = numeroCartao.value.replace(/\D/g, '').slice(0, 16);
            numeroCartao.value = valor.replace(/(\d{4})(?=\d)/g, '$1 ');
        });
    }

    const validadeCartao = document.getElementById('validadeCartao');
    if (validadeCartao) {
        validadeCartao.addEventListener('input', function () {
            let valor = validadeCartao.value.replace(/\D/g, '').slice(0, 4);
            if (valor.length > 2) {
                valor = valor.slice(0, 2) + '/' + valor.slice(2);
            }
            validadeCartao.value = valor;
        });
    }

    const formPagamento = document.getElementById('formPagamento');
    if (formPagamento) {
        formPagamento.addEventListener('submit', function () {
            // Garantia extra: mesmo que algum campo de cartão ganhe um
            // atributo "name" no futuro, ele é removido do envio aqui.
            ['numeroCartao', 'cvvCartao', 'validadeCartao', 'nomeCartao'].forEach(function (id) {
                const campo = document.getElementById(id);
                if (campo) {
                    campo.removeAttribute('name');
                }
            });
        });
    }
});
