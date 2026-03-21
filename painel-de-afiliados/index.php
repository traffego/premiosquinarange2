<?php
require_once(__DIR__ . '/config.php');

// ─── PROCESSAR AÇÕES (POST) ─────────────────────────────────────
$msg = '';
$msg_type = '';
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// LOGOUT
if ($action === 'logout') {
    aff_logout();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// LOGIN
if ($action === 'login') {
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone']);

    if (strlen($phone) < 10) {
        $msg = 'Telefone inválido.';
        $msg_type = 'error';
    } else {
        $stmt = $conn->prepare("SELECT * FROM customer_list WHERE phone = ? LIMIT 1");
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $msg = 'Telefone não encontrado. Faça seu cadastro.';
            $msg_type = 'error';
        } else {
            $user = $result->fetch_assoc();
            $stmt->close();

            // Se não é afiliado, torna automaticamente
            if ($user['is_affiliate'] != 1) {
                $code = generate_referral_code($conn);
                $pct  = get_default_percentage($conn);
                $ins  = $conn->prepare("INSERT INTO referral (customer_id, referral_code, percentage, amount_pending, amount_paid, status) VALUES (?, ?, ?, 0, 0, 1)");
                $ins->bind_param('isi', $user['id'], $code, $pct);
                $ins->execute();
                $ins->close();
                $conn->query("UPDATE customer_list SET is_affiliate = 1 WHERE id = " . intval($user['id']));
            }

            aff_login($user);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// CADASTRO
if ($action === 'register') {
    $firstname = trim($_POST['firstname']);
    $lastname  = trim($_POST['lastname']);
    $phone     = preg_replace('/[^0-9]/', '', $_POST['phone']);

    if (!$firstname || !$lastname || !$phone) {
        $msg = 'Preencha todos os campos.';
        $msg_type = 'error';
    } elseif (strlen($phone) < 10) {
        $msg = 'Telefone inválido.';
        $msg_type = 'error';
    } else {
        // Verifica se telefone já existe
        $chk = $conn->prepare("SELECT id, is_affiliate FROM customer_list WHERE phone = ? LIMIT 1");
        $chk->bind_param('s', $phone);
        $chk->execute();
        $res = $chk->get_result();

        if ($res->num_rows > 0) {
            $existing = $res->fetch_assoc();
            $chk->close();

            if ($existing['is_affiliate'] != 1) {
                $code = generate_referral_code($conn);
                $pct  = get_default_percentage($conn);
                $ins  = $conn->prepare("INSERT INTO referral (customer_id, referral_code, percentage, amount_pending, amount_paid, status) VALUES (?, ?, ?, 0, 0, 1)");
                $ins->bind_param('isi', $existing['id'], $code, $pct);
                $ins->execute();
                $ins->close();
                $conn->query("UPDATE customer_list SET is_affiliate = 1 WHERE id = " . intval($existing['id']));
            }

            $user = $conn->query("SELECT * FROM customer_list WHERE id = " . intval($existing['id']))->fetch_assoc();
            aff_login($user);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        $chk->close();

        $ins = $conn->prepare("INSERT INTO customer_list (firstname, lastname, phone, is_affiliate) VALUES (?, ?, ?, 1)");
        $ins->bind_param('sss', $firstname, $lastname, $phone);

        if (!$ins->execute()) {
            $msg = 'Erro ao criar conta. Tente novamente.';
            $msg_type = 'error';
        } else {
            $new_id = $conn->insert_id;
            $ins->close();

            $code = generate_referral_code($conn);
            $pct  = get_default_percentage($conn);
            $ref  = $conn->prepare("INSERT INTO referral (customer_id, referral_code, percentage, amount_pending, amount_paid, status) VALUES (?, ?, ?, 0, 0, 1)");
            $ref->bind_param('isi', $new_id, $code, $pct);
            $ref->execute();
            $ref->close();

            $user = $conn->query("SELECT * FROM customer_list WHERE id = " . intval($new_id))->fetch_assoc();
            aff_login($user);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// ─── DADOS DO DASHBOARD (se logado) ──────────────────────────────
$referral_code = '';
$aff_link = '';
$amount_pending = 0;
$amount_paid = 0;
$percentage = 10;
$total_vendas = 0;
$vendas = [];

if (aff_logged_in()) {
    $uid = intval(aff_user('id'));

    $ref = $conn->query("SELECT * FROM referral WHERE customer_id = $uid AND status = 1 LIMIT 1");
    if ($ref && $ref->num_rows > 0) {
        $r = $ref->fetch_assoc();
        $referral_code  = $r['referral_code'];
        $amount_pending = $r['amount_pending'];
        $amount_paid    = $r['amount_paid'];
        $percentage     = $r['percentage'];
        $aff_link       = BASE_REF . '?&ref=' . $referral_code;

        // Vendas
        $sales = $conn->query("SELECT COUNT(id) as total FROM order_list WHERE referral_id = '" . $conn->real_escape_string($referral_code) . "'");
        if ($sales && $sales->num_rows > 0) {
            $total_vendas = intval($sales->fetch_assoc()['total']);
        }

        // Lista de vendas
        $list = $conn->query("
            SELECT o.id, o.total_amount, o.quantity, o.status, o.date_created,
                   c.firstname, c.lastname,
                   p.name as product_name
            FROM order_list o
            LEFT JOIN customer_list c ON c.id = o.customer_id
            LEFT JOIN product_list p ON p.id = o.product_id
            WHERE o.referral_id = '" . $conn->real_escape_string($referral_code) . "'
            ORDER BY o.date_created DESC
            LIMIT 50
        ");
        if ($list) {
            while ($row = $list->fetch_assoc()) {
                $vendas[] = $row;
            }
        }
    }
}

// ─── HELPERS ─────────────────────────────────────────────────────
function generate_referral_code($conn) {
    do {
        $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        $check = $conn->query("SELECT id FROM referral WHERE referral_code = '" . $conn->real_escape_string($code) . "' LIMIT 1");
    } while ($check && $check->num_rows > 0);
    return $code;
}

function get_default_percentage($conn) {
    $q = $conn->query("SELECT meta_value FROM system_info WHERE meta_field = 'default_affiliate_percentage' LIMIT 1");
    if ($q && $q->num_rows > 0) {
        $val = intval($q->fetch_assoc()['meta_value']);
        return $val > 0 ? $val : 10;
    }
    return 10;
}

function status_badge($status) {
    if ($status == 2) return '<span class="aff-badge pago">Pago</span>';
    if ($status == 3) return '<span class="aff-badge cancelado">Cancelado</span>';
    return '<span class="aff-badge pendente">Pendente</span>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no">
    <title>Painel de Afiliados</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="aff-container">

    <!-- ═══ HEADER ═══ -->
    <div class="aff-header">
        <a href="<?= BASE_URL ?>"><img src="<?= BASE_URL ?>uploads/logo.png" alt="Logo"></a>
        <h1>Painel de Afiliados</h1>
        <p>Ganhe comissões indicando para seus amigos</p>
    </div>

    <?php if (!aff_logged_in()): ?>
    <!-- ═══════════════════════════════════════════════════════════
         AUTH: LOGIN / CADASTRO
         ═══════════════════════════════════════════════════════════ -->

    <?php if ($msg): ?>
        <div class="aff-msg <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="aff-tabs">
        <button class="aff-tab <?= ($action !== 'register') ? 'active' : '' ?>" onclick="showTab('login')">Entrar</button>
        <button class="aff-tab <?= ($action === 'register') ? 'active' : '' ?>" onclick="showTab('register')">Cadastrar</button>
    </div>

    <!-- Login -->
    <form id="form-login" class="aff-form <?= ($action !== 'register') ? 'active' : '' ?>"
          method="POST" action="">
        <input type="hidden" name="action" value="login">
        <div class="aff-field">
            <label>Telefone</label>
            <input type="tel" name="phone" placeholder="(00) 00000-0000"
                   maxlength="15" oninput="maskPhone(this)" required>
        </div>
        <button type="submit" class="aff-btn">Entrar</button>
    </form>

    <!-- Cadastro -->
    <form id="form-register" class="aff-form <?= ($action === 'register') ? 'active' : '' ?>"
          method="POST" action="">
        <input type="hidden" name="action" value="register">
        <div class="aff-field">
            <label>Nome</label>
            <input type="text" name="firstname" placeholder="Seu nome"
                   value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>" required>
        </div>
        <div class="aff-field">
            <label>Sobrenome</label>
            <input type="text" name="lastname" placeholder="Seu sobrenome"
                   value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>" required>
        </div>
        <div class="aff-field">
            <label>Telefone</label>
            <input type="tel" name="phone" placeholder="(00) 00000-0000"
                   maxlength="15" oninput="maskPhone(this)" required>
        </div>
        <button type="submit" class="aff-btn">🚀 Criar conta e ser Afiliado</button>
    </form>

    <?php else: ?>
    <!-- ═══════════════════════════════════════════════════════════
         DASHBOARD
         ═══════════════════════════════════════════════════════════ -->

    <!-- Welcome -->
    <div class="aff-welcome">
        <h2>Olá, <span><?= htmlspecialchars(aff_user('firstname')) ?></span></h2>
        <form method="POST" action="" style="margin:0">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="aff-logout">Sair</button>
        </form>
    </div>

    <!-- Stats -->
    <div class="aff-stats">
        <div class="aff-stat">
            <div class="aff-stat-label">💰 Saldo Pendente</div>
            <div class="aff-stat-value green">R$<?= number_format($amount_pending, 2, ',', '.') ?></div>
        </div>
        <div class="aff-stat">
            <div class="aff-stat-label">✅ Total Retirado</div>
            <div class="aff-stat-value accent">R$<?= number_format($amount_paid, 2, ',', '.') ?></div>
        </div>
        <div class="aff-stat">
            <div class="aff-stat-label">📊 Total Vendas</div>
            <div class="aff-stat-value"><?= $total_vendas ?></div>
        </div>
        <div class="aff-stat">
            <div class="aff-stat-label">📈 Comissão</div>
            <div class="aff-stat-value yellow"><?= $percentage ?>%</div>
        </div>
    </div>

    <!-- Link -->
    <div class="aff-link-card">
        <h3>🔗 Seu link de indicação</h3>
        <div class="aff-link-row">
            <input type="text" id="aff-link" readonly value="<?= htmlspecialchars($aff_link) ?>">
            <button class="aff-copy-btn" onclick="copyLink()">📋 Copiar</button>
        </div>
    </div>

    <!-- Vendas -->
    <div class="aff-section-title">📋 Últimas Vendas</div>
    <div class="aff-vendas-list">
        <?php if (empty($vendas)): ?>
            <div class="aff-empty">
                <span>📭</span>
                Nenhuma venda registrada ainda.<br>Compartilhe seu link!
            </div>
        <?php else: ?>
            <?php foreach ($vendas as $v): ?>
                <div class="aff-venda-item">
                    <div class="aff-venda-info">
                        <h4><?= htmlspecialchars($v['product_name'] ?: 'Pedido #' . $v['id']) ?></h4>
                        <p><?= htmlspecialchars($v['firstname'] . ' ' . $v['lastname']) ?> · <?= date('d/m/Y H:i', strtotime($v['date_created'])) ?></p>
                    </div>
                    <div class="aff-venda-right">
                        <div class="aff-venda-valor">R$<?= number_format($v['total_amount'], 2, ',', '.') ?></div>
                        <?= status_badge($v['status']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php endif; ?>

</div>

<script>
function showTab(tab) {
    document.querySelectorAll('.aff-tab').forEach(function(t, i) {
        t.classList.toggle('active', (tab === 'login' ? i === 0 : i === 1));
    });
    document.getElementById('form-login').classList.toggle('active', tab === 'login');
    document.getElementById('form-register').classList.toggle('active', tab === 'register');
}

function maskPhone(input) {
    var v = input.value.replace(/\D/g, '');
    if (v.length <= 10) {
        v = v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
    } else {
        v = v.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
    }
    input.value = v;
}

function copyLink() {
    var input = document.getElementById('aff-link');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(function() {
        var btn = document.querySelector('.aff-copy-btn');
        btn.textContent = '✅ Copiado!';
        setTimeout(function() { btn.textContent = '📋 Copiar'; }, 2000);
    }).catch(function() {
        document.execCommand('copy');
    });
}
</script>

</body>
</html>
