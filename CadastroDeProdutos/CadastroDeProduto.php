<?php
require_once '../connection.php';

$categorias_query = $conn->query("SELECT id_categoria, nome FROM categoria");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validação dos campos obrigatórios
    if (empty($_POST['nome_php']) || empty($_POST['categoria_php']) || empty($_POST['descricao_php']) || empty($_POST['preco_php'])) {
        echo "<script>alert('Preencha todos os campos obrigatórios!'); window.history.back();</script>";
        exit;
    }

    $nome = htmlspecialchars(trim($_POST['nome_php']));
    $id_categoria = intval($_POST['categoria_php']);
    $descricao = htmlspecialchars(trim($_POST['descricao_php']));
    $preco = floatval($_POST['preco_php']);

    $genero = !empty($_POST['genero_php']) ? htmlspecialchars($_POST['genero_php']) : 'Unissex';

    $marca = "Genérica";
    $dimensoes = 0.00;
    $peso = 0.00;
    $estoque = 10;
    $imagem_nome = "padrao.png";

    if (isset($_FILES['imagem_php']) && $_FILES['imagem_php']['error'] == 0) {
        $nome_original = $_FILES['imagem_php']['name'];
        $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
        $imagem_nome = uniqid() . "." . $extensao;

        $diretorio_destino = "../Index/";
        move_uploaded_file($_FILES['imagem_php']['tmp_name'], $diretorio_destino . $imagem_nome);
    }

    $stmt = $conn->prepare("
        INSERT INTO produto 
        (marca, id_categoria, dimensoes, nome, genero, estoque, descricao, imagem, peso, preco) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        die("Erro na preparação: " . $conn->error);
    }

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
    <link rel="stylesheet" href="https://jsdelivr.net">
</head>

<body>

<div class="TopBar">
    <h1>Cadastro de Produtos</h1>
</div>

<form action="CadastroDeProduto.php" method="POST" enctype="multipart/form-data" id="meuFormCadastro">

    <div class="page">

        <div class="lado-foto">
            <div class="photo-area" onclick="document.getElementById('photo-input').click()">
                <input type="file" id="photo-input" name="imagem_php" accept="image/*">
                <img id="preview-img" alt="Preview do produto">
                <i class="ti ti-camera-plus" aria-hidden="true" id="cam-icon"></i>
                <p id="photo-label">Adicionar foto do produto</p>
                <span id="photo-hint">PNG, JPG ou WEBP</span>
            </div>
        </div>

        <div class="lado-form">

            <div class="form-header">
                <div class="dot"></div>
                <h1>Cadastro de produto</h1>
            </div>

            <div class="grid-1">
                <label for="nome">Nome do produto</label>
                <input type="text" id="nome" name="nome_php" placeholder="Ex: Tênis Nike Jordan 4 Toro Bravo">
            </div>

            <div class="grid-2">

                <div>
                    <label for="categoria">Categoria</label>
                    <select id="categoria" name="categoria_php">
                        <option value="" disabled selected>Selecione...</option>

                        <?php
                        if ($categorias_query && $categorias_query->num_rows > 0) {
                            while ($cat = $categorias_query->fetch_assoc()) {
                                echo "<option value='" . $cat['id_categoria'] . "'>" . $cat['nome'] . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label>Gênero (opcional)</label>

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

            <div class="grid-1">
                <label for="preco">Preço (R$)</label>
                <input type="number" id="preco" name="preco_php" placeholder="Ex: 199.90" step="0.01" min="0" required>
            </div>

            <div class="grid-1">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao_php" required
                    placeholder="Descreva o produto: material, marca, características..."></textarea>
            </div>

            <button type="button" class="btn-submit" onclick="submitForm()">
                <i class="ti ti-device-floppy" aria-hidden="true"></i>
                Cadastrar produto
            </button>

        </div>

    </div>

</form>

<script src="CadastroDeProduto.js"></script>

</body>
</html>