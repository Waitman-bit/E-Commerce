<?php
session_start();

// Proteção: redireciona se não estiver logado
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Simulação de dados do usuário (substituir por consulta real ao banco)
// Exemplo de como buscar do banco:
// $id = $_SESSION['id'];
// $sql = "SELECT * FROM usuarios WHERE id = $id";
// $result = $conn->query($sql);
// $usuario = $result->fetch_assoc();

$usuario = [
    'id'     => $_SESSION['id']       ?? 1,
    'nome'   => $_SESSION['nome']     ?? 'Rafael Souza',
    'email'  => $_SESSION['email']    ?? 'rafael@email.com',
    'cpf'    => $_SESSION['cpf']      ?? '123.456.789-00',
    'tel'    => $_SESSION['tel']      ?? '(11) 99999-8888',
    'cep'    => $_SESSION['cep']      ?? '01310-100',
    'tipo'   => $_SESSION['tipo']     ?? 'cliente', // 'admin' ou 'cliente'
    'avatar' => $_SESSION['avatar']   ?? '',
];

$isAdmin = ($usuario['tipo'] === 'admin');

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
    <a class="navbar-logo" href="index.php">TITAN<span>SPORTS</span></a>
    <div class="navbar-actions">
        <a href="index.php"><i class="fa fa-store"></i> Loja</a>
        <?php if ($isAdmin): ?>
        <a href="admin.php"><i class="fa fa-chart-bar"></i> Admin</a>
        <?php endif; ?>
        <a href="perfil.php" class="active"><i class="fa fa-user"></i> Perfil</a>
    </div>
</nav>

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
                <div class="avatar-edit-btn" onclick="openModal('modalEditar')">
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
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// Fecha modal ao clicar fora
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});
</script>
</body>
</html>