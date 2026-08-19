<?php
session_start();
require_once('../connection.php');

function formatarPreco($valor) {
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

$mensagem = '';
$itensCarrinho = [];

if (isset($_SESSION['carrinho_mensagem'])) {
    $mensagem = $_SESSION['carrinho_mensagem'];
    unset($_SESSION['carrinho_mensagem']);
}

if (isset($_GET['remove']) && isset($_GET['id'])) {
    $idProduto = intval($_GET['id']);

    if ($idProduto > 0 && isset($_SESSION['carrinho'][$idProduto])) {
        unset($_SESSION['carrinho'][$idProduto]);
        $_SESSION['carrinho_mensagem'] = 'Produto removido do carrinho.';
        header('Location: carrinho.php');
        exit;
    }
}

if (isset($_GET['add']) && isset($_GET['id'])) {
    $idProduto = intval($_GET['id']);
    $quantidade = isset($_GET['quantidade']) ? max(1, intval($_GET['quantidade'])) : 1;

    if ($idProduto > 0) {
        $stmt = $conn->prepare('SELECT id_produto, nome, preco, imagem FROM produto WHERE id_produto = ?');
        $stmt->bind_param('i', $idProduto);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $produto = $resultado->fetch_assoc();

        if ($produto) {
            if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
                $_SESSION['carrinho'] = [];
            }

            $carrinho = $_SESSION['carrinho'];

            if (isset($carrinho[$idProduto])) {
                $carrinho[$idProduto]['quantidade'] += $quantidade;
            } else {
                $carrinho[$idProduto] = [
                    'id' => $produto['id_produto'],
                    'nome' => $produto['nome'],
                    'preco' => (float) $produto['preco'],
                    'imagem' => $produto['imagem'],
                    'quantidade' => $quantidade
                ];
            }

            $_SESSION['carrinho'] = $carrinho;
            $_SESSION['carrinho_mensagem'] = 'Produto adicionado ao carrinho.';
            header('Location: carrinho.php');
            exit;
        } else {
            $_SESSION['carrinho_mensagem'] = 'Produto não encontrado.';
            header('Location: carrinho.php');
            exit;
        }
    }
}

if (isset($_SESSION['carrinho']) && is_array($_SESSION['carrinho'])) {
    $itensCarrinho = $_SESSION['carrinho'];
}

$totalCarrinho = 0;
foreach ($itensCarrinho as $item) {
    $totalCarrinho += $item['preco'] * $item['quantidade'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="carrinho.css">
    <title>Carrinho</title>
</head>
<body>
    <div class="TopBar">
        <h1>Meu Carrinho</h1>
        <a href="../Index/Index.php">Voltar Para a Loja</a>
    </div>

    <div class="conteudo">
        <?php if (!empty($mensagem)): ?>
            <p class="mensagem-sucesso"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>

        <?php if (empty($itensCarrinho)): ?>
            <div class="carrinho-vazio">
                <h2>Seu carrinho está vazio.</h2> <br>
                <div id="botao-explore">
                    <button>
                        <a href="../Index/Index.php">Explore Nossos Produtos</a>
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="lista-itens">
                <?php foreach ($itensCarrinho as $item): ?>
                    <div class="item-carrinho">
                        <img src="../ImagensProdutos/<?php echo htmlspecialchars($item['imagem']); ?>" alt="<?php echo htmlspecialchars($item['nome']); ?>">
                        <div class="item-info">
                            <h3><?php echo htmlspecialchars($item['nome']); ?></h3>
                            <p>Quantidade: <?php echo intval($item['quantidade']); ?></p>
                            <p>Subtotal: <?php echo formatarPreco($item['preco'] * $item['quantidade']); ?></p>
                        </div>
                        <a class="btn-remover" href="carrinho.php?remove=1&id=<?php echo intval($item['id']); ?>">Remover</a>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="resumo-carrinho">
                <h2>Total do carrinho</h2>
                <p><?php echo formatarPreco($totalCarrinho); ?></p>
                <a href="../Checkout/checkout.php" class="btn-finalizar-compra">Finalizar Compra</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>