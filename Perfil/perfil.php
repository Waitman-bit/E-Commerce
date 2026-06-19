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
    <style>
        /* ── TOKENS ─────────────────────────────────────────────── */
        :root {
            --black:      #0a0a0a;
            --surface:    #111111;
            --card:       #181818;
            --border:     #242424;
            --yellow:     #FFD100;
            --yellow-dim: #c9a700;
            --yellow-glow: rgba(255, 209, 0, 0.18);
            --red:        #e03333;
            --text:       #f0f0f0;
            --muted:      #888;
            --radius:     12px;
            --font-disp:  'Rajdhani', sans-serif;
            --font-body:  'Inter', sans-serif;
        }

        /* ── RESET ───────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--black);
            color: var(--text);
            font-family: var(--font-body);
            min-height: 100vh;
        }
        a { text-decoration: none; color: inherit; }

        /* ── NAVBAR ──────────────────────────────────────────────── */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            height: 64px;
        }
        .navbar-logo {
            font-family: var(--font-disp);
            font-size: 1.7rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--yellow);
        }
        .navbar-logo span { color: var(--text); }
        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .navbar-actions a {
            color: var(--muted);
            font-size: 0.85rem;
            font-weight: 500;
            transition: color .2s;
        }
        .navbar-actions a:hover { color: var(--yellow); }
        .navbar-actions a.active { color: var(--yellow); }

        /* ── CONTAINER ────────────────────────────────────────────── */
        .page {
            max-width: 1080px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 1.5rem;
            align-items: start;
        }

        /* ── SIDEBAR (esquerda) ──────────────────────────────────── */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        /* Avatar card */
        .avatar-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2rem 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .avatar-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--yellow);
        }
        .avatar-wrap {
            position: relative;
            width: 96px;
            height: 96px;
            margin: 0 auto 1rem;
        }
        .avatar-img {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--yellow);
            background: var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .avatar-placeholder {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            border: 3px solid var(--yellow);
            background: #1e1e1e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--yellow);
            margin: 0 auto 1rem;
        }
        .avatar-edit-btn {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 28px;
            height: 28px;
            background: var(--yellow);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            color: #000;
            cursor: pointer;
            transition: transform .2s;
        }
        .avatar-edit-btn:hover { transform: scale(1.15); }
        .avatar-name {
            font-family: var(--font-disp);
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: .25rem;
        }
        .avatar-email {
            font-size: 0.8rem;
            color: var(--muted);
            margin-bottom: .85rem;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .3rem .8rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: .5px;
        }
        .badge-admin {
            background: var(--yellow);
            color: #000;
        }
        .badge-cliente {
            background: rgba(255,209,0,.1);
            color: var(--yellow);
            border: 1px solid var(--yellow-dim);
        }

        /* Ações sidebar */
        .actions-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }
        .actions-card-title {
            padding: .85rem 1.25rem;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
        }
        .action-btn {
            display: flex;
            align-items: center;
            gap: .85rem;
            padding: .9rem 1.25rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text);
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            cursor: pointer;
            border-bottom: 1px solid var(--border);
            transition: background .15s, color .15s;
        }
        .action-btn:last-child { border-bottom: none; }
        .action-btn:hover {
            background: var(--yellow-glow);
            color: var(--yellow);
        }
        .action-btn .icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #222;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            color: var(--yellow);
            flex-shrink: 0;
            transition: background .15s;
        }
        .action-btn:hover .icon { background: var(--yellow); color: #000; }
        .action-btn.danger { color: #e05555; }
        .action-btn.danger .icon { color: #e05555; }
        .action-btn.danger:hover { background: rgba(224,51,51,.1); color: var(--red); }
        .action-btn.danger:hover .icon { background: var(--red); color: #fff; }

        /* ── MAIN (direita) ──────────────────────────────────────── */
        .main { display: flex; flex-direction: column; gap: 1.25rem; }

        /* Section title */
        .section-title {
            font-family: var(--font-disp);
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--yellow);
            margin-bottom: .1rem;
        }
        .section-sub {
            font-size: 0.78rem;
            color: var(--muted);
            margin-bottom: 1rem;
        }

        /* Info grid */
        .info-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: .85rem;
        }
        .info-item {
            background: #111;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .85rem 1rem;
            transition: border-color .2s, box-shadow .2s;
        }
        .info-item:hover {
            border-color: var(--yellow-dim);
            box-shadow: 0 0 0 2px var(--yellow-glow);
        }
        .info-label {
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: .3rem;
            display: flex;
            align-items: center;
            gap: .4rem;
        }
        .info-label i { color: var(--yellow); font-size: .7rem; }
        .info-value {
            font-size: 0.92rem;
            font-weight: 500;
            color: var(--text);
        }

        /* Pedidos */
        .orders-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }
        .orders-card-head {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        .orders-table thead th {
            padding: .75rem 1.5rem;
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 1.4px;
            text-transform: uppercase;
            color: var(--muted);
            text-align: left;
            background: #111;
            border-bottom: 1px solid var(--border);
        }
        .orders-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background .15s;
        }
        .orders-table tbody tr:last-child { border-bottom: none; }
        .orders-table tbody tr:hover { background: #1a1a1a; }
        .orders-table td {
            padding: 1rem 1.5rem;
            font-size: 0.85rem;
        }
        .orders-table td:first-child {
            font-family: var(--font-disp);
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--yellow);
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .25rem .7rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
        }
        .status-entregue  { background: rgba(34,197,94,.12); color: #4ade80; }
        .status-pendente  { background: rgba(234,179,8,.12);  color: #facc15; }
        .status-cancelado { background: rgba(239,68,68,.12);  color: #f87171; }

        /* Admin panel CTA */
        .admin-cta {
            background: linear-gradient(135deg, #181800 0%, #1c1800 100%);
            border: 1px solid var(--yellow-dim);
            border-radius: var(--radius);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }
        .admin-cta-icon {
            width: 52px;
            height: 52px;
            flex-shrink: 0;
            background: var(--yellow);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #000;
        }
        .admin-cta h3 {
            font-family: var(--font-disp);
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: .25rem;
        }
        .admin-cta p { font-size: 0.8rem; color: var(--muted); margin-bottom: .75rem; }
        .btn-yellow {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: var(--yellow);
            color: #000;
            border: none;
            border-radius: 8px;
            padding: .55rem 1.25rem;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, transform .15s;
        }
        .btn-yellow:hover { background: #e8bc00; transform: translateY(-1px); }

        /* ── MODAL EDITAR PERFIL ─────────────────────────────────── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.75);
            z-index: 200;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            width: 100%;
            max-width: 480px;
            padding: 2rem;
            position: relative;
        }
        .modal-title {
            font-family: var(--font-disp);
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--yellow);
            margin-bottom: 1.5rem;
        }
        .modal-close {
            position: absolute;
            top: 1rem; right: 1rem;
            background: none;
            border: none;
            color: var(--muted);
            font-size: 1.1rem;
            cursor: pointer;
        }
        .modal-close:hover { color: var(--text); }
        .form-group { margin-bottom: 1rem; }
        .form-label {
            display: block;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: .4rem;
        }
        .form-input {
            width: 100%;
            background: #111;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .75rem 1rem;
            color: var(--text);
            font-family: var(--font-body);
            font-size: 0.9rem;
            outline: none;
            transition: border-color .2s;
        }
        .form-input:focus {
            border-color: var(--yellow-dim);
            box-shadow: 0 0 0 3px var(--yellow-glow);
        }
        .modal-actions {
            display: flex;
            gap: .75rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        .btn-ghost {
            background: none;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .55rem 1.2rem;
            color: var(--muted);
            font-size: 0.85rem;
            cursor: pointer;
            transition: color .2s, border-color .2s;
        }
        .btn-ghost:hover { color: var(--text); border-color: #444; }

        /* ── RESPONSIVE ──────────────────────────────────────────── */
        @media (max-width: 768px) {
            .page { grid-template-columns: 1fr; }
            .info-grid { grid-template-columns: 1fr; }
            .orders-table thead { display: none; }
            .orders-table td {
                display: flex;
                padding: .5rem 1rem;
            }
            .orders-table tr {
                display: flex;
                flex-wrap: wrap;
                padding: .5rem 0;
            }
        }
    </style>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar">
    <a class="navbar-logo" href="index.php">SPORT<span>ZONE</span></a>
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

            <a href="pedidos.php" class="action-btn">
                <span class="icon"><i class="fa fa-box"></i></span>
                Meus pedidos
            </a>

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
                <a href="admin.php" class="btn-yellow">
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
                    <div class="info-label"><i class="fa fa-hashtag"></i> ID</div>
                    <div class="info-value">#<?= htmlspecialchars($usuario['id']) ?></div>
                </div>
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
