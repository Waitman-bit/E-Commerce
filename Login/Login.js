document.addEventListener('DOMContentLoaded', function() {

    const telefone = document.getElementById('telefone');
    if (telefone) {
        telefone.addEventListener('input', function() {
            this.value = this.value
                .replace(/\D/g, '')
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{5})(\d)/, '$1-$2')
                .replace(/(-\d{4})\d+?$/, '$1');
        });
    }

    const cpf = document.querySelector('input[name="cpf"]');
    if (cpf) {
        cpf.addEventListener('input', function() {
            this.value = this.value
                .replace(/\D/g, '')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2')
                .replace(/(-\d{2})\d+?$/, '$1');
        });
    }

    const cep = document.querySelector('input[name="cep"]');
    if (cep) {
        cep.addEventListener('input', function() {
            this.value = this.value
                .replace(/\D/g, '')
                .replace(/(\d{5})(\d)/, '$1-$2')
                .replace(/(-\d{3})\d+?$/, '$1');
        });
    }

    const container = document.getElementById('container');
    const registerBtn = document.getElementById('register');
    const loginBtn = document.getElementById('login');

    registerBtn.addEventListener('click', () => {
        container.classList.add("active");
    });

    loginBtn.addEventListener('click', () => {
        container.classList.remove("active");
    });

});