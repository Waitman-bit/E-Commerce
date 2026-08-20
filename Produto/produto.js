/**
 * SportZone - Lógica de interação da página de produto
 * Responsável por: Comprar Agora, Adicionar ao Carrinho e exibição de Toast
 */

document.addEventListener('DOMContentLoaded', function () {

    const btnComprarAgora = document.getElementById('btnComprarAgora');
    const btnAdicionarCarrinho = document.getElementById('btnAdicionarCarrinho');
    const inputQuantidade = document.getElementById('quantidade');
    let toast = document.getElementById('toast');

    function garantirToast() {
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toast';
            toast.className = 'toast';
            document.body.appendChild(toast);
        }

        return toast;
    }

    /**
     * Exibe uma mensagem toast temporária na tela
     * @param {string} mensagem - texto a exibir
     * @param {string} tipo - 'sucesso' ou 'erro'
     */
    function exibirToast(mensagem, tipo = 'sucesso') {
        const toastAtual = garantirToast();
        toastAtual.textContent = mensagem;
        toastAtual.classList.remove('toast-erro');
        if (tipo === 'erro') {
            toastAtual.classList.add('toast-erro');
        }
        toastAtual.classList.add('toast-show');

        // Remove o toast automaticamente após 3 segundos
        setTimeout(() => {
            toastAtual.classList.remove('toast-show');
        }, 3000);
    }

    function atualizarContadorCarrinho(quantidadeTotal) {
        let cartBadge = document.querySelector('.cart-badge');

        if (!cartBadge) {
            const cartLink = document.querySelector('.cart-link');
            if (!cartLink) {
                return;
            }

            cartBadge = document.createElement('span');
            cartBadge.className = 'cart-badge';
            cartLink.appendChild(cartBadge);
        }

        const quantidade = Number(quantidadeTotal) || 0;
        cartBadge.textContent = String(quantidade);
        cartBadge.dataset.cartCount = String(quantidade);
        cartBadge.style.display = quantidade > 0 ? 'flex' : 'none';
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
                window.location.href = '../Login/Login.php';
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
                window.location.href = '../Login/Login.php';
                return;
            }

            const idProduto = btnAdicionarCarrinho.dataset.id;
            const quantidade = obterQuantidade();
            const urlAdicionar = '../Carrinho/carrinho.php?add=1&id=' + encodeURIComponent(idProduto)
                + '&quantidade=' + encodeURIComponent(quantidade)
                + '&ajax=1';

            btnAdicionarCarrinho.disabled = true;
            btnAdicionarCarrinho.textContent = 'Adicionando...';

            fetch(urlAdicionar, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(async function (resp) {
                    const dados = resp.headers.get('content-type')?.includes('application/json')
                        ? await resp.json()
                        : {};

                    if (!resp.ok || !dados.success) {
                        throw new Error(dados.mensagem || 'Não foi possível adicionar o produto ao carrinho.');
                    }

                    atualizarContadorCarrinho(dados.quantidade_total || 0);
                    exibirToast(dados.mensagem || 'Produto adicionado ao carrinho.');
                })
                .catch(function (erro) {
                    exibirToast(erro.message || 'Não foi possível adicionar o produto ao carrinho.', 'erro');
                })
                .finally(function () {
                    btnAdicionarCarrinho.disabled = false;
                    btnAdicionarCarrinho.textContent = 'Adicionar ao Carrinho';
                });
        });
    }
});