<?php
// ARIKAWA BACK END - CONEXAO COM O BANCO DE DADOS
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/../connection.php';

// ARIKAWA BACK END - BUSCA AS CATEGORIAS REGISTRADAS
$categorias_query = $conn->query("SELECT id_categoria, nome FROM categoria");
$tamanhos_query = $conn->query('SELECT id_tamanho, descricao FROM tamanho ORDER BY id_tamanho');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = $_POST['nome_php'];
    $id_categoria = $_POST['categoria_php'];
    $descricao = $_POST['descricao_php'];
    
    // O campo usa pt-BR (ex.: 1.300,00); converta para decimal antes do float.
    $precoTexto = trim((string) ($_POST['preco_php'] ?? '0'));
    $preco = (float) str_replace(',', '.', str_replace('.', '', $precoTexto));

    $genero = !empty($_POST['genero_php']) ? $_POST['genero_php'] : 'Unissex';

    $marca = "Generica";
    $dimensoes = 0.00;
    $peso = 0.00;
    $tamanhos = json_decode($_POST['tamanhos_php'] ?? '[]', true);
    if (!is_array($tamanhos)) {
        die('Dados de tamanhos inválidos.');
    }

    $tamanhosValidados = [];
    $buscarTamanho = $conn->prepare('SELECT id_tamanho FROM tamanho WHERE descricao = ? LIMIT 1');
    foreach ($tamanhos as $tamanho) {
        $descricaoTamanho = trim((string) ($tamanho['tamanho'] ?? ''));
        $estoqueTamanho = filter_var($tamanho['estoque'] ?? null, FILTER_VALIDATE_INT);

        if ($descricaoTamanho === '' || strlen($descricaoTamanho) > 10 || $estoqueTamanho === false || $estoqueTamanho <= 0 || isset($tamanhosValidados[$descricaoTamanho])) {
            die('Os tamanhos e estoques informados sao invalidos.');
        }

        $buscarTamanho->bind_param('s', $descricaoTamanho);
        $buscarTamanho->execute();
        if (!$buscarTamanho->get_result()->fetch_assoc()) {
            die('Um dos tamanhos selecionados nao existe no banco de dados.');
        }

        $tamanhosValidados[$descricaoTamanho] = $estoqueTamanho;
    }
    $buscarTamanho->close();
    // Nome padrão caso nenhuma imagem seja enviada
    $imagem_nome = "sem-imagem.svg";

    function uploadErrorMessage($code) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'O arquivo excede upload_max_filesize.',
            UPLOAD_ERR_FORM_SIZE  => 'O arquivo excede o limite do formulário.',
            UPLOAD_ERR_PARTIAL    => 'O upload foi feito parcialmente.',
            UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo foi enviado.',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária ausente.',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar o arquivo em disco.',
            UPLOAD_ERR_EXTENSION  => 'Upload interrompido por extensão do PHP.',
        ];
        return $errors[$code] ?? 'Erro de upload desconhecido.';
    }

    // Upload da imagem
    if (isset($_FILES['imagem_php'])) {
        if ($_FILES['imagem_php']['error'] !== UPLOAD_ERR_OK) {
            die('Erro no upload da imagem: ' . uploadErrorMessage($_FILES['imagem_php']['error']));
        }

        $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

        $nome_original = $_FILES['imagem_php']['name'];
        $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));

        if (in_array($extensao, $extensoesPermitidas)) {

            // Gera um nome único
            $imagem_nome = uniqid('produto_', true) . "." . $extensao;

            // Pasta onde as imagens ficarão
            $diretorio_destino = "../ImagensProdutos/";

            // Cria a pasta caso ela não exista
            if (!is_dir($diretorio_destino) && !mkdir($diretorio_destino, 0777, true)) {
                die('Erro ao criar pasta de upload. Verifique permissions in "ImagensProdutos".');
            }

            // Move a imagem para a pasta
            if (!move_uploaded_file(
                    $_FILES['imagem_php']['tmp_name'], 
                    $diretorio_destino . $imagem_nome
                )) {

                die('Erro ao salvar a imagem. Verifique se o servidor tem permissão de escrita no diretório "ImagensProdutos".');
            }

        } else {
            die("Formato de imagem inválido.");
        }
    }

    $stmt = $conn->prepare(
    "INSERT INTO produto
    (marca, id_categoria, dimensoes, nome, genero, descricao, imagem, peso, preco)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

    $stmt->bind_param(
        "sidssssdd",
        $marca,
        $id_categoria,
        $dimensoes,
        $nome,
        $genero,
        $descricao,
        $imagem_nome,
        $peso,
        $preco
    );

    $conn->begin_transaction();

    try {
        if (!$stmt->execute()) {
            throw new RuntimeException('Nao foi possivel cadastrar o produto: ' . $stmt->error);
        }

        $idProduto = $conn->insert_id;

        if (!empty($tamanhosValidados)) {
            $buscarTamanho = $conn->prepare('SELECT id_tamanho FROM tamanho WHERE descricao = ? LIMIT 1');
            $vincularEstoque = $conn->prepare('INSERT INTO produto_tamanho (id_produto, id_tamanho, estoque) VALUES (?, ?, ?)');

            foreach ($tamanhosValidados as $descricaoTamanho => $estoqueTamanho) {
            $buscarTamanho->bind_param('s', $descricaoTamanho);
            $buscarTamanho->execute();
            $tamanhoExistente = $buscarTamanho->get_result()->fetch_assoc();
            $idTamanho = (int) $tamanhoExistente['id_tamanho'];

            $vincularEstoque->bind_param('iii', $idProduto, $idTamanho, $estoqueTamanho);
            if (!$vincularEstoque->execute()) {
                throw new RuntimeException('Nao foi possivel registrar o estoque por tamanho: ' . $vincularEstoque->error);
            }
        }

            $buscarTamanho->close();
            $vincularEstoque->close();
        }

        $conn->commit();
        echo "<script>
                alert('Produto cadastrado com sucesso!');
                window.location.href='CadastroDeProduto.php';
              </script>";
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('Erro cadastro produto: ' . $e->getMessage());
        echo "<script>
                alert('Erro ao cadastrar produto e estoque: " . addslashes($e->getMessage()) . "');
              </script>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" href="../logoicon.ico" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Produto</title>
    <!-- IMPORTACAO DO TABLER ICONS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="CadastroDeProduto.css">
</head>
<body>
<!-- WAITMAN FRONT END - ESTRUTURA VISUAL DO TOPO DA PAGINA COM O BOTÃO NO LUGAR DO TEXTO -->
<div class="TopBar">
    <a href="../Administrador/Adm.php" class="topbar-back-button">
        <i class="ti ti-arrow-left"> </i> Painel administrativo
    </a>
        <h1>Cadastro de Produto</h1>
</div>

<form 
    action="CadastroDeProduto.php" 
    method="POST" 
    enctype="multipart/form-data" 
    id="meuFormCadastro"
    onsubmit="return submitForm()"
>

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
                <h1>Cadastro de Produto</h1>
            </div>

            <div class="grid-1">
                <label for="nome">Nome do Produto</label>
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
                    <label>Genêro (opcional)</label>
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
        <?php if ($tamanhos_query && $tamanhos_query->num_rows > 0): ?>
            <?php while ($tamanho = $tamanhos_query->fetch_assoc()): ?>
                <?php $ehUnico = in_array(mb_strtoupper(trim($tamanho['descricao']), 'UTF-8'), ['UNICO', 'ÚNICO', 'TAMANHO ÚNICO'], true); ?>
                <button type="button" class="size-pill<?= $ehUnico ? ' unico' : '' ?>" onclick="<?= $ehUnico ? 'toggleUniqueSize(this)' : 'toggleSize(this)' ?>" data-size="<?= htmlspecialchars($tamanho['descricao'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($tamanho['descricao'], ENT_QUOTES, 'UTF-8') ?></button>
            <?php endwhile; ?>
        <?php else: ?>
            <span class="size-empty">Nenhum tamanho cadastrado no banco.</span>
        <?php endif; ?>
        </div>
    <div id="estoque-tamanhos" class="estoque-tamanhos"></div>
    <!-- Guarda os tamanhos e estoques para o PHP -->
    <input type="hidden" name="tamanhos_php" id="tamanhos_php">
</div>
            <div class="grid-1">
                <label for="preco">Preço (R$)</label>
                <input type="text" inputmode="decimal" id="preco" name="preco_php" placeholder="Ex: 199,90" autocomplete="off" spellcheck="false">
            </div>

            <div class="grid-1">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao_php" placeholder="Descreva o produto: material, marca, características..."></textarea>
            </div>

            <button type="submit" class="btn-submit">
                Cadastrar Produto
            </button>

        </div>
    </div>
</form>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="CadastroDeProduto.js"></script>
</body>
</html>
