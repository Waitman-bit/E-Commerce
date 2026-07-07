<?php
// ARIKAWA BACK END - CONEXAO COM O BANCO DE DADOS
require_once '../connection.php';

// ARIKAWA BACK END - BUSCA AS CATEGORIAS REGISTRADAS
$categorias_query = $conn->query("SELECT id_categoria, nome FROM categoria");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = $_POST['nome_php'];
    $id_categoria = $_POST['categoria_php'];
    $descricao = $_POST['descricao_php'];
    
    // MORITA BANCOS DE DADOS - CAPTURA O PRECO E CONVERTE PARA NUMERO DECIMAL
    $preco = isset($_POST['preco_php']) ? floatval($_POST['preco_php']) : 0.00; 

    $genero = !empty($_POST['genero_php']) ? $_POST['genero_php'] : 'Unissex';

    $marca = "Generica";
    $dimensoes = 0.00;
    $peso = 0.00;
    $estoque = 10;
   // Nome padrão caso nenhuma imagem seja enviada
$imagem_nome = "padrao.png";

// Upload da imagem
if (isset($_FILES['imagem_php']) && $_FILES['imagem_php']['error'] === UPLOAD_ERR_OK) {

    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

    $nome_original = $_FILES['imagem_php']['name'];
    $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));

    if (in_array($extensao, $extensoesPermitidas)) {

        // Gera um nome único
        $imagem_nome = uniqid('produto_', true) . "." . $extensao;

        // Pasta onde as imagens ficarão
        $diretorio_destino = "../ImagensProdutos/";

        // Cria a pasta caso ela não exista
        if (!is_dir($diretorio_destino)) {
            mkdir($diretorio_destino, 0777, true);
        }

        // Move a imagem para a pasta
        if (!move_uploaded_file(
                $_FILES['imagem_php']['tmp_name'],
                $diretorio_destino . $imagem_nome
            )) {

            die("Erro ao salvar a imagem.");
        }

    } else {
        die("Formato de imagem inválido.");
    }
}

    // MORITA BANCOS DE DADOS - INSERCAO DO NOVO CAMPO PRECO NA TABELA
    $stmt = $conn->prepare("
        INSERT INTO produto 
        (marca, id_categoria, dimensoes, nome, genero, estoque, descricao, imagem, peso, preco) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // MORITA BANCOS DE DADOS - VINCULO DOS PARAMETROS ADICIONANDO O PRECO DECIMAL
    $stmt->bind_param(
        "sidssissdd", 
        $marca,
        $id_categoria,
        $dimensoes,
        $nome,
        $genero,
        $estoque,
        $descricao,
        $imagem_nome,
        $peso,
        $preco
    );

    if ($stmt->execute()) {
        echo "<script>
                alert('Produto cadastrado com sucesso!');
                window.location.href='CadastroDeProduto.php';
              </script>";
    } else {
        echo "<script>
                alert('Erro ao cadastrar no banco: " . $stmt->error . "');
              </script>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Produto</title>

    <link rel="stylesheet" href="CadastroDeProduto.css">
    <!-- WAITMAN FRONT END - IMPORTACAO DA BIBLIOTECA OFICIAL TABLER ICONS -->
    <link rel="stylesheet" href="https://jsdelivr.net">
</head>

<body>

<!-- WAITMAN FRONT END - ESTRUTURA VISUAL DO TOPO DA PAGINA -->
<div class="TopBar">
    <h1>Cadastro de Produtos</h1>
</div>

<form action="CadastroDeProduto.php" method="POST" enctype="multipart/form-data" id="meuFormCadastro">

    <div class="page">

        <!-- WAITMAN FRONT END - AREA DE INTERACAO PARA ADICIONAR FOTO -->
        <div class="lado-foto">
            <div class="photo-area" onclick="document.getElementById('photo-input').click()">
                <input type="file" id="photo-input" name="imagem_php" accept="image/*">
                <img id="preview-img" alt="Preview do produto">
                <i class="ti ti-camera-plus" aria-hidden="true" id="cam-icon"></i>
                <p id="photo-label">Adicionar foto do produto</p>
                <span id="photo-hint">PNG, JPG ou WEBP</span>
            </div>
        </div>

        <!-- WAITMAN FRONT END - SECAO COM OS CAMPOS DE TEXTO E SELECAO -->
        <div class="lado-form">

            <div class="form-header">
                <div class="dot"></div>
                <h1>Cadastro de produto</h1>
            </div>

            <div class="grid-1">
                <label for="nome">Nome do produto</label>
                <input type="text" id="nome" name="nome_php" placeholder="Ex: Tenis Nike Jordan 4 Toro Bravo">
            </div>

            <div class="grid-2">

                <div>
                    <label for="categoria">Categoria</label>
                    <select id="categoria" name="categoria_php">
                        <option value="" disabled selected>Selecione...</option>

                        <?php
                        // ARIKAWA BACK END - EXIBICAO DAS OPCOES DO BANCO DE DADOS
                        if ($categorias_query && $categorias_query->num_rows > 0) {
                            while ($cat = $categorias_query->fetch_assoc()) {
                                echo "<option value='" . $cat['id_categoria'] . "'>" . $cat['nome'] . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label>Genero (opcional)</label>

                    <input type="hidden" name="genero_php" id="genero_oculto" value="">

                    <div class="gender-group">
                        <button type="button" class="gender-btn" onclick="selectGender(this, 'Masculino')">
                            <i class="ti ti-man" aria-hidden="true"></i> Homem
                        </button>

                        <button type="button" class="gender-btn" onclick="selectGender(this, 'Feminino')">
                            <i class="ti ti-woman" aria-hidden="true"></i> Mulher
                        </button>
                    </div>
                </div>

            </div>

            <div class="grid-1 mt-16">
                <label>Tamanho</label>

                <div class="size-group">
                    <button type="button" class="size-pill" onclick="toggleSize(this)">PP</button>
                    <button type="button" class="size-pill" onclick="toggleSize(this)">P</button>
                    <button type="button" class="size-pill" onclick="toggleSize(this)">M</button>
                    <button type="button" class="size-pill" onclick="toggleSize(this)">G</button>
                    <button type="button" class="size-pill" onclick="toggleSize(this)">GG</button>
                    <button type="button" class="size-pill" onclick="toggleSize(this)">XG</button>
                    <button type="button" class="size-pill" onclick="toggleSize(this)">38</button>
                    <button type="button" class="size-pill" onclick="toggleSize(this)">39</button>
                    <button type="button" class="size-pill" onclick="toggleSize(this)">40</button>
                    <button type="button" class="size-pill" onclick="toggleSize(this)">41</button>
                    <button type="button" class="size-pill" onclick="toggleSize(this)">42</button>
                    <button type="button" class="size-pill" onclick="toggleSize(this)">43</button>
                </div>
            </div>

            <!-- MORITA BANCOS DE DADOS - INPUT PARA RECEBER O VALOR DO PRODUTO -->
            <div class="grid-1">
                <label for="preco">Preco (R$)</label>
                <input type="number" step="0.01" min="0" id="preco" name="preco_php" placeholder="Ex: 199.90" onblur="formatarPreco(this)">
            </div>

            <div class="grid-1">
                <label for="descricao">Descricao</label>
                <textarea id="descricao" name="descricao_php"
                    placeholder="Descreva o produto: material, marca, caracteristicas..."></textarea>
            </div>

            <!-- ARIKAWA BACK END - ENVIO SEGURO DO FORMULARIO COMPLETO -->
            <button type="button" class="btn-submit" onclick="submitForm()">
                <i class="ti ti-device-floppy" aria-hidden="true"></i>
                Cadastrar produto
            </button>

        </div>

    </div>

</form>

<script src="CadastroDeProduto.js"></script>
<script src="https://code.jquery.com/jquery-4.0.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js"></script>

</body>
</html>
