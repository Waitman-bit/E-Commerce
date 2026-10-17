document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('container');
    const registerBtn = document.getElementById('register');
    const loginBtn = document.getElementById('login');

    registerBtn.addEventListener('click', () => {
        container.classList.add("active");
    });

    loginBtn.addEventListener('click', () => {
        container.classList.remove("active");
    });
    const telefone = document.getElementById('telefone');
    if (telefone) {
        telefone.addEventListener('input', function () {
            const digitos = telefone.value.replace(/\D/g, '').slice(0, 11);
            const ddd = digitos.slice(0, 2);
            const numero = digitos.slice(2);
            let formatado = ddd ? '(' + ddd : '';

            if (digitos.length >= 2) formatado += ') ';
            if (numero.length > 0) {
                const limitePrefixo = digitos.length > 10 ? 5 : 4;
                formatado += numero.slice(0, limitePrefixo);
                if (numero.length > limitePrefixo) {
                    formatado += '-' + numero.slice(limitePrefixo, limitePrefixo + 4);
                }
            }

            telefone.value = formatado;
        });
    }
    $('#cpf').mask('000.000.000-00', {reverse: true});
    $('#cep').mask('00000-000');
});


