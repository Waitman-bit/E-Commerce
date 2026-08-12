/**
 * SportZone - Lógica de interação da página de produto
 * Responsável por: Comprar Agora, Adicionar ao Carrinho e exibição de Toast
 */

document.addEventListener('DOMContentLoaded', function () {

    const btnComprarAgora = document.getElementById('btnComprarAgora');
    const btnAdicionarCarrinho = document.getElementById('btnAdicionarCarrinho');
    const inputQuantidade = document.getElementById('quantidade');
    const toast = document.getElementById('toast');

    /**
     * Exibe uma mensagem toast temporária na tela
     * @param {string} mensagem - texto a exibir
     * @param {string} tipo - 'sucesso' ou 'erro'
     */
    function exibirToast(mensagem, tipo = 'sucesso') {
        toast.textContent = mensagem;
        toast.classList.remove('toast-erro');
        if (tipo === 'erro') {
            toast.classList.add('toast-erro');
        }
        toast.classList.add('toast-show');

        // Remove o toast automaticamente após 3 segundos
        setTimeout(() => {
            toast.classList.remove('toast-show');
        }, 3000);
    }

    /**
     * Captura a quantidade selecionada pelo usuário (mínimo 1)
     */
    function obterQuantidade() {
        if (!inputQuantidade) return 1;
        const valor = parseInt(inputQuantidade.value, 10);
        return (isNaN(valor) || valor < 1) ? 1 : valor;
    }

    // ===== BOTÃO: COMPRAR AGORA =====
    // Não existe (nem deve existir) um "criar_pedido.php": o pedido só é
    // gravado de verdade dentro da transação em Pagamento/pagamento.php,
    // depois que o cliente escolhe entrega e forma de pagamento.
    // "Comprar Agora" então faz a mesma coisa que "Adicionar ao Carrinho"
    // (usa o endpoint real de carrinho.php?add=1), só que em seguida já
    // manda o usuário direto para o checkout, em vez de voltar ao carrinho.
    if (btnComprarAgora) {
        btnComprarAgora.addEventListener('click', function () {

            // Verificação extra de segurança no front-end
            if (!USUARIO_LOGADO) {
                window.location.href = '../Login/login.php';
                return;
            }

            const idProduto = btnComprarAgora.dataset.id;
            const quantidade = obterQuantidade();

            btnComprarAgora.disabled = true;
            btnComprarAgora.textContent = 'Processando...';

            const urlAdicionar = '../Carrinho/carrinho.php?add=1&id=' + encodeURIComponent(idProduto)
                + '&quantidade=' + encodeURIComponent(quantidade);

            // Chama o mesmo endpoint de adicionar ao carrinho (carrinho.php
            // responde com um redirect para carrinho.php, que o fetch segue
            // sozinho; só precisamos aguardar terminar) e então seguimos
            // direto para o checkout.
            fetch(urlAdicionar)
                .then(function (resp) {
                    if (!resp.ok) {
                        throw new Error('Falha ao adicionar o produto ao carrinho.');
                    }
                    window.location.href = '../Checkout/checkout.php';
                })
                .catch(function () {
                    exibirToast('Não foi possível concluir a compra. Tente novamente.', 'erro');
                    btnComprarAgora.disabled = false;
                    btnComprarAgora.textContent = 'Comprar Agora';
                });
        });
    }

    // ===== BOTÃO: ADICIONAR AO CARRINHO =====
    if (btnAdicionarCarrinho) {
        btnAdicionarCarrinho.addEventListener('click', function () {

            if (!USUARIO_LOGADO) {
                window.location.href = '../Login/login.php';
                return;
            }

            const idProduto = btnAdicionarCarrinho.dataset.id;
            const quantidade = obterQuantidade();

            btnAdicionarCarrinho.disabled = true;
            btnAdicionarCarrinho.textContent = 'Adicionando...';

            window.location.href = '../Carrinho/carrinho.php?add=1&id=' + encodeURIComponent(idProduto) + '&quantidade=' + encodeURIComponent(quantidade);
        });
    }
});