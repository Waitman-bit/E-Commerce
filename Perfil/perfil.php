<?php
session_start();

// Proteção: redireciona se não estiver logado
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Carregar dados do usuário do banco de dados
require_once '../connection.php';

function buildAvatarUrl($valor) {
    if (empty($valor)) {
        return '';
    }

    if (preg_match('#^https?://#i', $valor)) {
        return $valor;
    }

    $valor = str_replace('\\', '/', $valor);

    if (strpos($valor, '/') === 0) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $valor;
    }

    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    $baseDir = $scriptDir !== '' ? $scriptDir : '';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $baseDir . '/' . ltrim($valor, '/');
}

$usuario_id = $_SESSION['id'];
$sql = "SELECT * FROM usuario WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();
$userData = $resultado->fetch_assoc();
$stmt->close();

// Atualizar sessão com dados do banco
$avatarPath = '';
if ($userData) {
    $_SESSION['nome']  = $userData['nome'];
    $_SESSION['email'] = $userData['email'];
    $_SESSION['cpf']   = $userData['cpf'];
    $_SESSION['tel']   = $userData['telefone'];
    $_SESSION['cep']   = $userData['cep'];
    $_SESSION['tipo']  = $userData['tipo'];
    $avatarPath = $userData['foto_perfil'] ?? '';
    $_SESSION['avatar'] = $avatarPath;
}

$avatarUrl = buildAvatarUrl($avatarPath);

$usuario = [
    'id'     => $_SESSION['id']       ?? 1,
    'nome'   => $_SESSION['nome']     ?? 'Rafael Souza',
    'email'  => $_SESSION['email']    ?? 'rafael@email.com',
    'cpf'    => $_SESSION['cpf']      ?? '123.456.789-00',
    'tel'    => $_SESSION['tel']      ?? '(11) 99999-8888',
    'cep'    => $_SESSION['cep']      ?? '01310-100',
    'tipo'   => $_SESSION['tipo']     ?? 'cliente', // 'admin' ou 'cliente'
    'avatar' => $avatarUrl,
];

$isAdmin = ($usuario['tipo'] === 'admin');

// Mensagens de sucesso/erro
$mensagem = '';
$tipo_mensagem = '';

if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] === 'avatar_atualizado') {
        $mensagem = '✓ Foto de perfil atualizada com sucesso!';
        $tipo_mensagem = 'sucesso';
    }
} elseif (isset($_GET['erro'])) {
    $erros = [
        'nenhum_arquivo' => 'Selecione uma imagem para fazer upload',
        'arquivo_grande' => 'A imagem é muito grande. Máximo: 5MB',
        'tipo_invalido' => 'Formato de arquivo não permitido. Use: JPG, PNG, GIF ou WebP',
        'extensao_invalida' => 'Extensão de arquivo não permitida',
        'falha_upload' => 'Erro ao fazer upload da imagem. Verifique as permissões da pasta',
        'falha_banco' => 'Erro ao salvar no banco de dados',
        'pasta_criacao' => 'Erro ao criar pasta de uploads. Verifique as permissões'
    ];
    $mensagem = $erros[$_GET['erro']] ?? 'Erro desconhecido: ' . htmlspecialchars($_GET['erro']);
    $tipo_mensagem = 'erro';
}

// Dados fictícios de pedidos recentes (substituir por query real)
$pedidos = [
    ['id' => '#00421', 'produto' => 'Tênis Nike Air Max',   'status' => 'entregue',  'data' => '12/06/2025', 'valor' => 'R$ 499,90'],
    ['id' => '#00389', 'produto' => 'Bola Adidas Pro',      'status' => 'pendente',  'data' => '08/06/2025', 'valor' => 'R$ 189,90'],
    ['id' => '#00301', 'produto' => 'Luva de Goleiro Penalty', 'status' => 'cancelado', 'data' => '01/06/2025', 'valor' => 'R$ 129,90'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil – SportZone</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="perfil.css">
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar">
    <a class="navbar-logo" href="../Index/Index.php">TITAN<span>SPORTS</span></a>
    <div class="navbar-actions">
        <a href="../Index/Index.php"><i class="fa fa-store"></i> Loja</a>
        <?php if ($isAdmin): ?>
        <a href="admin.php"><i class="fa fa-chart-bar"></i> Admin</a>
        <?php endif; ?>
        <a href="perfil.php" class="active"><i class="fa fa-user"></i> Perfil</a>
    </div>
</nav>

<!-- ── MENSAGENS ── -->
<?php if (!empty($mensagem)): ?>
<div class="alert alert-<?= $tipo_mensagem ?>">
    <div class="alert-content">
        <i class="fa fa-<?= $tipo_mensagem === 'sucesso' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <span><?= htmlspecialchars($mensagem) ?></span>
    </div>
    <button class="alert-close" onclick="this.parentElement.style.display='none'">
        <i class="fa fa-xmark"></i>
    </button>
</div>
<?php endif; ?>

<!-- ── PAGE GRID ── -->
<div class="page">

    <!-- ── SIDEBAR ── -->
    <aside class="sidebar">

        <!-- Avatar Card -->
        <div class="avatar-card">
            <div class="avatar-wrap">
                <?php if (!empty($usuario['avatar'])): ?>
                    <img src="<?= htmlspecialchars($usuario['avatar']) ?>"
                         alt="Avatar" class="avatar-img">
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <i class="fa fa-user"></i>
                    </div>
                <?php endif; ?>
                <div class="avatar-edit-btn" onclick="openModal('modalAvatar')">
                    <i class="fa fa-pen"></i>
                </div>
            </div>
            <div class="avatar-name"><?= htmlspecialchars($usuario['nome']) ?></div>
            <div class="avatar-email"><?= htmlspecialchars($usuario['email']) ?></div>
            <?php if ($isAdmin): ?>
                <span class="badge badge-admin"><i class="fa fa-bolt"></i> Admin</span>
            <?php else: ?>
                <span class="badge badge-cliente"><i class="fa fa-user-check"></i> Cliente</span>
            <?php endif; ?>
        </div>

        <!-- Ações -->
        <div class="actions-card">
            <div class="actions-card-title">Menu</div>

            <button class="action-btn" onclick="openModal('modalEditar')">
                <span class="icon"><i class="fa fa-pen"></i></span>
                Editar perfil
            </button>

            <button class="action-btn" onclick="openModal('modalSenha')">
                <span class="icon"><i class="fa fa-lock"></i></span>
                Alterar senha
            </button>

            <?php if ($isAdmin): ?>
            <a href="admin.php" class="action-btn">
                <span class="icon"><i class="fa fa-chart-line"></i></span>
                Painel Admin
            </a>
            <a href="cadastro_produto.php" class="action-btn">
                <span class="icon"><i class="fa fa-plus"></i></span>
                Novo produto
            </a>
            <?php endif; ?>

            <a href="logout.php" class="action-btn danger"
               onclick="return confirm('Tem certeza que deseja sair?')">
                <span class="icon"><i class="fa fa-right-from-bracket"></i></span>
                Sair da conta
            </a>
        </div>

    </aside>

    <!-- ── MAIN ── -->
    <main class="main">

        <!-- Admin CTA -->
        <?php if ($isAdmin): ?>
        <div class="admin-cta">
            <div class="admin-cta-icon"><i class="fa fa-bolt"></i></div>
            <div>
                <h3>Acesso Administrador</h3>
                <p>Gerencie produtos, pedidos e usuários no painel completo.</p>
                <a href="../Administrador/Adm.php" class="btn-yellow">
                    <i class="fa fa-gauge-high"></i> Abrir Painel
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Informações do Perfil -->
        <div class="info-card">
            <div class="section-title">Informações</div>
            <div class="section-sub">Seus dados cadastrados na plataforma</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label"><i class="fa fa-user"></i> Nome</div>
                    <div class="info-value"><?= htmlspecialchars($usuario['nome']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fa fa-envelope"></i> Email</div>
                    <div class="info-value"><?= htmlspecialchars($usuario['email']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fa fa-id-card"></i> CPF</div>
                    <div class="info-value"><?= htmlspecialchars($usuario['cpf']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fa fa-phone"></i> Telefone</div>
                    <div class="info-value"><?= htmlspecialchars($usuario['tel']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fa fa-location-dot"></i> CEP</div>
                    <div class="info-value"><?= htmlspecialchars($usuario['cep']) ?></div>
                </div>
            </div>
        </div>

        <!-- Pedidos Recentes (só para clientes) -->
        <?php if (!$isAdmin): ?>
        <div class="orders-card">
            <div class="orders-card-head">
                <div class="section-title">Pedidos Recentes</div>
                <div class="section-sub">Seus últimos 3 pedidos</div>
            </div>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Produto</th>
                        <th>Data</th>
                        <th>Valor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['id']) ?></td>
                        <td><?= htmlspecialchars($p['produto']) ?></td>
                        <td><?= htmlspecialchars($p['data']) ?></td>
                        <td><?= htmlspecialchars($p['valor']) ?></td>
                        <td>
                            <span class="status-badge status-<?= $p['status'] ?>">
                                <?php
                                $icons = ['entregue'=>'fa-circle-check','pendente'=>'fa-clock','cancelado'=>'fa-circle-xmark'];
                                echo '<i class="fa '.$icons[$p['status']].'"></i> '.ucfirst($p['status']);
                                ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </main>
</div>

<!-- ── MODAL: EDITAR PERFIL ── -->
<div class="modal-overlay" id="modalEditar">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('modalEditar')">
            <i class="fa fa-xmark"></i>
        </button>
        <div class="modal-title"><i class="fa fa-pen"></i> Editar Perfil</div>
        <form method="POST" action="atualizar_perfil.php">
            <div class="form-group">
                <label class="form-label">Nome completo</label>
                <input type="text" name="nome" class="form-input"
                       value="<?= htmlspecialchars($usuario['nome']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input"
                       value="<?= htmlspecialchars($usuario['email']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Telefone</label>
                <input type="tel" name="tel" class="form-input"
                       value="<?= htmlspecialchars($usuario['tel']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">CEP</label>
                <input type="text" name="cep" class="form-input"
                       value="<?= htmlspecialchars($usuario['cep']) ?>">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-ghost" onclick="closeModal('modalEditar')">Cancelar</button>
                <button type="submit" class="btn-yellow"><i class="fa fa-save"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL: ALTERAR FOTO DE PERFIL ── -->
<div class="modal-overlay" id="modalAvatar">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('modalAvatar')">
            <i class="fa fa-xmark"></i>
        </button>
        <div class="modal-title"><i class="fa fa-image"></i> Alterar Foto de Perfil</div>
        
        <form id="formAvatar" method="POST" action="atualizar_avatar.php" enctype="multipart/form-data">
            <div class="form-group">
                <div class="avatar-preview-container">
                    <div class="avatar-preview" id="avatarPreview">
                        <?php if (!empty($usuario['avatar'])): ?>
                            <img src="<?= htmlspecialchars($usuario['avatar']) ?>" alt="Prévia">
                        <?php else: ?>
                            <i class="fa fa-image"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Selecione uma imagem</label>
                <div class="file-input-wrapper">
                    <input type="file" id="inputAvatar" name="avatar" class="file-input"
                           accept="image/jpeg,image/png,image/gif,image/webp"
                           onchange="previewAvatar(event)" required>
                    <label for="inputAvatar" class="file-input-label">
                        <i class="fa fa-cloud-arrow-up"></i>
                        <span>Clique para selecionar ou arraste uma imagem</span>
                    </label>
                </div>
                <div class="form-help">Formatos aceitos: JPG, PNG, GIF, WebP. Máximo: 5MB</div>
                <span class="form-error" id="erro-avatar"></span>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-ghost" onclick="closeModal('modalAvatar')">
                    <i class="fa fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn-yellow" id="btnSalvarAvatar">
                    <i class="fa fa-save"></i> Salvar Foto
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL: ALTERAR SENHA ── -->
<div class="modal-overlay" id="modalSenha">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('modalSenha')">
            <i class="fa fa-xmark"></i>
        </button>
        <div class="modal-title"><i class="fa fa-lock"></i> Alterar Senha</div>
        <form method="POST" action="alterar_senha.php">
            <div class="form-group">
                <label class="form-label">Senha atual</label>
                <input type="password" name="senha_atual" class="form-input"
                       placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nova senha</label>
                <input type="password" name="nova_senha" class="form-input"
                       placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label class="form-label">Confirmar nova senha</label>
                <input type="password" name="confirmar_senha" class="form-input"
                       placeholder="••••••••" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-ghost" onclick="closeModal('modalSenha')">Cancelar</button>
                <button type="submit" class="btn-yellow"><i class="fa fa-check"></i> Alterar</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── FUNÇÕES DE MODAL ──
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// Fecha modal ao clicar fora
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});

// ── UPLOAD DE AVATAR ──
function previewAvatar(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('avatarPreview');
    const erroElement = document.getElementById('erro-avatar');
    
    erroElement.textContent = '';
    
    if (!file) return;
    
    // Validar tamanho (5MB máximo)
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        erroElement.textContent = 'Imagem muito grande. Máximo: 5MB (Seu arquivo: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB)';
        event.target.value = '';
        return;
    }
    
    // Validar tipo de arquivo (verificar MIME type)
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        erroElement.textContent = 'Formato não permitido. Use: JPG, PNG, GIF ou WebP (Detectado: ' + file.type + ')';
        event.target.value = '';
        return;
    }
    
    // Validar extensão
    const allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    const ext = file.name.split('.').pop().toLowerCase();
    if (!allowedExt.includes(ext)) {
        erroElement.textContent = 'Extensão não permitida. Use: jpg, jpeg, png, gif ou webp';
        event.target.value = '';
        return;
    }
    
    // Carregar preview
    const reader = new FileReader();
    reader.onload = function(e) {
        preview.innerHTML = '<img src="' + e.target.result + '" alt="Prévia">';
    };
    reader.readAsDataURL(file);
}

// Validar formulário antes de enviar
document.getElementById('formAvatar')?.addEventListener('submit', function(e) {
    const fileInput = document.getElementById('inputAvatar');
    const erroElement = document.getElementById('erro-avatar');
    
    if (!fileInput.files || !fileInput.files[0]) {
        e.preventDefault();
        erroElement.textContent = 'Selecione uma imagem';
    }
});

// Suporte a drag and drop
const fileInput = document.getElementById('inputAvatar');
if (fileInput) {
    const dropZone = fileInput.parentElement;
    
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });
    
    dropZone.addEventListener('dragleave', function() {
        dropZone.classList.remove('dragover');
    });
    
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        fileInput.files = e.dataTransfer.files;
        previewAvatar({ target: fileInput });
    });
}
</script>
</body>
</html>