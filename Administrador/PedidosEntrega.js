document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.linha-pedido').forEach(function (linha) {
        linha.querySelector('.btn-expandir').addEventListener('click', function () {
            const alvoId = linha.getAttribute('data-alvo');
            const alvo = document.getElementById(alvoId);
            if (!alvo) return;

            const aberto = alvo.style.display !== 'none';
            alvo.style.display = aberto ? 'none' : 'table-row';
            this.textContent = aberto ? '+' : '−';
        });
    });

    const toast = document.getElementById('toastCopia');

    function mostrarToast() {
        toast.classList.add('visivel');
        setTimeout(function () {
            toast.classList.remove('visivel');
        }, 2000);
    }

    document.querySelectorAll('.btn-copiar').forEach(function (botao) {
        botao.addEventListener('click', async function () {
            const texto = botao.getAttribute('data-texto');
            try {
                await navigator.clipboard.writeText(texto);
                mostrarToast();
            } catch (erro) {
                // Fallback para navegadores sem suporte ao Clipboard API
                const textarea = document.createElement('textarea');
                textarea.value = texto;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                mostrarToast();
            }
        });
    });
});
