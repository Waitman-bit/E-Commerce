<?php
/**
 * checkout.php
 *
 * Página de checkout do Titan Sports.
 * Utiliza o carrinho já existente em $_SESSION['carrinho'], a sessão de login
 * já existente ($_SESSION['id'], etc.) e reaproveita Navbar.php e connection.php.
 *
 * Não cria nenhuma tabela/coluna nova. Usa apenas: produto, usuario, pedido,
 * item_pedido, pagamento, entrega.
 */

session_start();
require_once('../connection.php');
require_once('frete.php');

// Checkout exige usuário autenticado (pedido.id_usuario é obrigatório no banco)
if (!isset($_SESSION['id'])) {
    $_SESSION['login_mensagem'] = 'Faça login para continuar a compra.';
    header('Location: ../Login/Login.php');
    exit;
}

// Sem itens no carrinho, não há o que finalizar
if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho']) || empty($_SESSION['carrinho'])) {
    header('Location: ../Carrinho/carrinho.php');
    exit;
}

$itensCarrinho = $_SESSION['carrinho'];

// Busca dados complementares do usuário logado para pré-preencher o formulário
$stmt = $conn->prepare('SELECT nome, email, cpf, telefone, cep FROM usuario WHERE id_usuario = ?');
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

function formatarPreco($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

$subtotal = 0;
foreach ($itensCarrinho as $item) {
    $subtotal += $item['preco'] * $item['quantidade'];
}

// Mensagem de erro vinda do processar_pedido.php (ex: validação falhou)
$erroGeral = '';
if (isset($_SESSION['checkout_erro'])) {
    $erroGeral = $_SESSION['checkout_erro'];
    unset($_SESSION['checkout_erro']);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Titan Sports</title>
    <?php require_once('../NavBar/Navbar.php'); ?>
    <link rel="stylesheet" href="checkout.css">
</head>
<body>



<div class="checkout-progress">
    <div class="progress-step done">
        <span class="step-circle"><i class="fas fa-check"></i></span>
        <span class="step-label">Carrinho</span>
    </div>
    <div class="progress-line done"></div>
    <div class="progress-step active">
        <span class="step-circle">2</span>
        <span class="step-label">Entrega e Pagamento</span>
    </div>
    <div class="progress-line"></div>
    <div class="progress-step">
        <span class="step-circle">3</span>
        <span class="step-label">Confirmação</span>
    </div>
</div>

<main class="checkout-container">

    <?php if ($erroGeral): ?>
        <div class="alerta-erro-geral"><i class="fas fa-triangle-exclamation"></i> <?php echo htmlspecialchars($erroGeral); ?></div>
    <?php endif; ?>

    <form id="form-checkout" action="processar_pedido.php" method="POST" novalidate>

        <div class="checkout-grid">

            <!-- ===================== COLUNA ESQUERDA ===================== -->
            <div class="checkout-coluna-esquerda">

                <!-- Dados do comprador -->
                <section class="card-checkout" id="secao-comprador">
                    <h2><i class="fas fa-user"></i> Dados do comprador</h2>

                    <div class="campo-grupo">
                        <label for="nome_completo">Nome completo</label>
                        <input type="text" id="nome_completo" name="nome_completo"
                               value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" required>
                        <span class="erro-campo" id="erro-nome_completo"></span>
                    </div>

                    <div class="campo-linha">
                        <div class="campo-grupo">
                            <label for="cpf">CPF</label>
                            <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00" maxlength="14"
                                   value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>" required>
                            <span class="erro-campo" id="erro-cpf"></span>
                        </div>
                        <div class="campo-grupo">
                            <label for="telefone">Telefone</label>
                            <input type="text" id="telefone" name="telefone" placeholder="(00) 00000-0000" maxlength="15"
                                   value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>" required>
                            <span class="erro-campo" id="erro-telefone"></span>
                        </div>
                    </div>

                    <div class="campo-grupo">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email"
                               value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
                        <span class="erro-campo" id="erro-email"></span>
                    </div>
                </section>

                <!-- Endereço de entrega -->
                <section class="card-checkout" id="secao-endereco">
                    <h2><i class="fas fa-truck"></i> Endereço de entrega</h2>

                    <div class="campo-linha campo-linha-cep">
                        <div class="campo-grupo campo-cep">
                            <label for="cep">CEP</label>
                            <input type="text" id="cep" name="cep" placeholder="00000-000" maxlength="9"
                                   value="<?php echo htmlspecialchars($usuario['cep'] ?? ''); ?>" required>
                            <span class="erro-campo" id="erro-cep"></span>
                        </div>
                        <span class="cep-status" id="cep-status"></span>
                    </div>

                    <div class="campo-grupo">
                        <label for="rua">Rua</label>
                        <input type="text" id="rua" name="rua" required>
                        <span class="erro-campo" id="erro-rua"></span>
                    </div>

                    <div class="campo-linha">
                        <div class="campo-grupo">
                            <label for="numero">Número</label>
                            <input type="text" id="numero" name="numero" required>
                            <span class="erro-campo" id="erro-numero"></span>
                        </div>
                        <div class="campo-grupo">
                            <label for="complemento">Complemento</label>
                            <input type="text" id="complemento" name="complemento">
                        </div>
                    </div>

                    <div class="campo-grupo">
                        <label for="bairro">Bairro</label>
                        <input type="text" id="bairro" name="bairro" required>
                        <span class="erro-campo" id="erro-bairro"></span>
                    </div>

                    <div class="campo-linha">
                        <div class="campo-grupo">
                            <label for="cidade">Cidade</label>
                            <input type="text" id="cidade" name="cidade" required>
                            <span class="erro-campo" id="erro-cidade"></span>
                        </div>
                        <div class="campo-grupo">
                            <label for="estado">Estado</label>
                            <input type="text" id="estado" name="estado" maxlength="2" required>
                            <span class="erro-campo" id="erro-estado"></span>
                        </div>
                    </div>
                </section>

                <!-- Método de entrega -->
                <section class="card-checkout" id="secao-entrega">
                    <h2><i class="fas fa-box"></i> Método de entrega</h2>

                    <div class="opcoes-entrega">
                        <label class="opcao-entrega" data-tipo="padrao">
                            <input type="radio" name="tipo_entrega" value="padrao" checked>
                            <div class="opcao-entrega-info">
                                <strong>Entrega padrão</strong>
                                <span class="opcao-prazo" data-campo="prazo">Informe o CEP</span>
                            </div>
                            <span class="opcao-valor" data-campo="valor">--</span>
                        </label>

                        <label class="opcao-entrega" data-tipo="expressa">
                            <input type="radio" name="tipo_entrega" value="expressa">
                            <div class="opcao-entrega-info">
                                <strong>Entrega expressa</strong>
                                <span class="opcao-prazo" data-campo="prazo">Informe o CEP</span>
                            </div>
                            <span class="opcao-valor" data-campo="valor">--</span>
                        </label>

                        <label class="opcao-entrega" data-tipo="retirada">
                            <input type="radio" name="tipo_entrega" value="retirada">
                            <div class="opcao-entrega-info">
                                <strong>Retirada na loja</strong>
                                <span class="opcao-prazo">Disponível em até 24h úteis</span>
                            </div>
                            <span class="opcao-valor">Grátis</span>
                        </label>
                    </div>

                    <input type="hidden" id="frete_valor" name="frete_valor" value="0">
                    <input type="hidden" id="frete_prazo" name="frete_prazo" value="">
                    <input type="hidden" id="frete_regiao" name="frete_regiao" value="">
                </section>

                <!-- Forma de pagamento -->
                <section class="card-checkout" id="secao-pagamento">
                    <h2><i class="fas fa-credit-card"></i> Forma de pagamento</h2>

                    <div class="opcoes-pagamento">
                        <label class="opcao-pagamento">
                            <input type="radio" name="forma_pagamento" value="cartao_credito" checked>
                            <i class="fas fa-credit-card"></i> Crédito
                        </label>
                        <label class="opcao-pagamento">
                            <input type="radio" name="forma_pagamento" value="cartao_debito">
                            <i class="fas fa-credit-card"></i> Débito
                        </label>
                        <label class="opcao-pagamento">
                            <input type="radio" name="forma_pagamento" value="pix">
                            <i class="fas fa-qrcode"></i> PIX
                        </label>
                        <label class="opcao-pagamento">
                            <input type="radio" name="forma_pagamento" value="boleto">
                            <i class="fas fa-barcode"></i> Boleto
                        </label>
                    </div>

                    <!-- Painel: Cartão (crédito/débito) -->
                    <div class="painel-pagamento" id="painel-cartao">
                        <div class="campo-grupo">
                            <label for="numero_cartao">Número do cartão</label>
                            <input type="text" id="numero_cartao" name="numero_cartao" placeholder="0000 0000 0000 0000" maxlength="19">
                            <span class="erro-campo" id="erro-numero_cartao"></span>
                        </div>
                        <div class="campo-grupo">
                            <label for="nome_cartao">Nome impresso no cartão</label>
                            <input type="text" id="nome_cartao" name="nome_cartao" placeholder="Como está no cartão">
                            <span class="erro-campo" id="erro-nome_cartao"></span>
                        </div>
                        <div class="campo-linha">
                            <div class="campo-grupo">
                                <label for="validade_cartao">Validade</label>
                                <input type="text" id="validade_cartao" name="validade_cartao" placeholder="MM/AA" maxlength="5">
                                <span class="erro-campo" id="erro-validade_cartao"></span>
                            </div>
                            <div class="campo-grupo">
                                <label for="cvv">CVV</label>
                                <input type="text" id="cvv" name="cvv" placeholder="000" maxlength="4">
                                <span class="erro-campo" id="erro-cvv"></span>
                            </div>
                        </div>
                        <div class="campo-grupo" id="grupo-parcelas">
                            <label for="parcelas">Parcelas</label>
                            <select id="parcelas" name="parcelas">
                                <option value="1">1x à vista</option>
                            </select>
                        </div>
                    </div>

                    <!-- Painel: PIX -->
                    <div class="painel-pagamento" id="painel-pix" style="display:none;">
                        <div class="pix-box">
                            <div class="pix-qrcode-placeholder">
                                <i class="fas fa-qrcode"></i>
                                <p>QR Code será gerado após a confirmação</p>
                            </div>
                            <div class="campo-grupo">
                                <label>Código copia e cola</label>
                                <div class="pix-copia-cola">
                                    <input type="text" id="pix_codigo" readonly
                                           value="00020126580014BR.GOV.BCB.PIX0136titan-sports-simulado5204000053039865802BR5913TitanSports">
                                    <button type="button" id="btn-copiar-pix">Copiar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Painel: Boleto -->
                    <div class="painel-pagamento" id="painel-boleto" style="display:none;">
                        <div class="boleto-box">
                            <p>Valor: <strong id="boleto-valor">R$ 0,00</strong></p>
                            <p>Vencimento: <strong id="boleto-vencimento">--</strong></p>
                            <button type="button" id="btn-gerar-boleto">Gerar boleto</button>
                        </div>
                    </div>
                </section>

            </div>

            <!-- ===================== COLUNA DIREITA ===================== -->
            <div class="checkout-coluna-direita">
                <section class="card-checkout resumo-pedido">
                    <h2><i class="fas fa-receipt"></i> Resumo do pedido</h2>

                    <div class="lista-resumo-itens">
                        <?php foreach ($itensCarrinho as $item): ?>
                            <div class="resumo-item">
                                <img src="../ImagensProdutos/<?php echo htmlspecialchars($item['imagem']); ?>"
                                     alt="<?php echo htmlspecialchars($item['nome']); ?>">
                                <div class="resumo-item-info">
                                    <span class="resumo-item-nome"><?php echo htmlspecialchars($item['nome']); ?></span>
                                    <span class="resumo-item-qtd">Qtd: <?php echo intval($item['quantidade']); ?></span>
                                </div>
                                <span class="resumo-item-preco"><?php echo formatarPreco($item['preco'] * $item['quantidade']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="cupom-box">
                        <input type="text" id="cupom_codigo" name="cupom_codigo" placeholder="Código do cupom">
                        <button type="button" id="btn-aplicar-cupom">Aplicar</button>
                    </div>
                    <span class="erro-campo" id="erro-cupom"></span>

                    <div class="resumo-totais">
                        <div class="linha-total">
                            <span>Subtotal</span>
                            <span id="resumo-subtotal"><?php echo formatarPreco($subtotal); ?></span>
                        </div>
                        <div class="linha-total">
                            <span>Frete</span>
                            <span id="resumo-frete">Informe o CEP</span>
                        </div>
                        <div class="linha-total" id="linha-desconto" style="display:none;">
                            <span>Desconto</span>
                            <span id="resumo-desconto">- R$ 0,00</span>
                        </div>
                        <div class="linha-total total-final">
                            <span>Total</span>
                            <span id="resumo-total"><?php echo formatarPreco($subtotal); ?></span>
                        </div>
                    </div>

                    <div class="selo-seguranca">
                        <i class="fas fa-lock"></i> Pagamento 100% seguro
                    </div>

                    <button type="submit" class="btn-finalizar" id="btn-finalizar">Finalizar Compra</button>
                </section>
            </div>

        </div>

        <input type="hidden" name="subtotal" id="input-subtotal" value="<?php echo $subtotal; ?>">
        <input type="hidden" name="desconto" id="input-desconto" value="0">
        <input type="hidden" name="cupom_aplicado" id="input-cupom-aplicado" value="">

    </form>

</main>

<script src="checkout.js"></script>
</body>
</html>
