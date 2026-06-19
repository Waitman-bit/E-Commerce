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

            // Envia requisição para criar o pedido no servidor
            fetch('criar_pedido.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    produto_id: idProduto,
                    quantidade: quantidade
                })
            })
            .then(resp => resp.json())
            .then(data => {
                if (data.sucesso) {
                    // Redireciona para o checkout após criar o pedido
                    window.location.href = 'checkout.php?pedido=' + data.pedido_id;
                } else {
                    exibirToast(data.mensagem || 'Não foi possível concluir a compra.', 'erro');
                    btnComprarAgora.disabled = false;
                    btnComprarAgora.textContent = 'Comprar Agora';
                }
            })
            .catch(() => {
                exibirToast('Erro de conexão. Tente novamente.', 'erro');
                btnComprarAgora.disabled = false;
                btnComprarAgora.textContent = 'Comprar Agora';
            });
        });
    }

    // ===== BOTÃO: ADICIONAR AO CARRINHO (AJAX) =====
    if (btnAdicionarCarrinho) {
        btnAdicionarCarrinho.addEventListener('click', function () {

            if (!USUARIO_LOGADO) {
                window.location.href = '../Login/login.php';
                return;
            }

            const idProduto = btnAdicionarCarrinho.dataset.id;
            const quantidade = obterQuantidade();

            btnAdicionarCarrinho.disabled = true;

            fetch('adicionar_carrinho.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    produto_id: idProduto,
                    quantidade: quantidade
                })
            })
            .then(resp => resp.json())
            .then(data => {
                btnAdicionarCarrinho.disabled = false;

                if (data.sucesso) {
                    exibirToast('Produto adicionado ao carrinho.', 'sucesso');
                } else {
                    exibirToast(data.mensagem || 'Erro ao adicionar produto.', 'erro');
                }
            })
            .catch(() => {
                btnAdicionarCarrinho.disabled = false;
                exibirToast('Erro de conexão. Tente novamente.', 'erro');
            });
        });
    }
});