<?php
require_once(__DIR__ . '/config.php');
$logged = aff_logged_in();
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

    <!-- ═══ AUTH (Login / Cadastro) ═══ -->
    <div id="auth-section" style="<?= $logged ? 'display:none' : '' ?>">

        <div class="aff-tabs">
            <button class="aff-tab active" onclick="switchTab('login')">Entrar</button>
            <button class="aff-tab" onclick="switchTab('register')">Cadastrar</button>
        </div>

        <div id="auth-msg" class="aff-msg"></div>

        <!-- Login -->
        <form id="form-login" class="aff-form active" onsubmit="return doLogin(event)">
            <div class="aff-field">
                <label>Telefone</label>
                <input type="tel" id="login-phone" placeholder="(00) 00000-0000"
                       maxlength="15" oninput="maskPhone(this)" required>
            </div>
            <button type="submit" class="aff-btn" id="btn-login">Entrar</button>
        </form>

        <!-- Cadastro -->
        <form id="form-register" class="aff-form" onsubmit="return doRegister(event)">
            <div class="aff-field">
                <label>Nome</label>
                <input type="text" id="reg-firstname" placeholder="Seu nome" required>
            </div>
            <div class="aff-field">
                <label>Sobrenome</label>
                <input type="text" id="reg-lastname" placeholder="Seu sobrenome" required>
            </div>
            <div class="aff-field">
                <label>Telefone</label>
                <input type="tel" id="reg-phone" placeholder="(00) 00000-0000"
                       maxlength="15" oninput="maskPhone(this)" required>
            </div>
            <button type="submit" class="aff-btn" id="btn-register">🚀 Criar conta e ser Afiliado</button>
        </form>
    </div>

    <!-- ═══ DASHBOARD ═══ -->
    <div id="dashboard-section" class="aff-dashboard <?= $logged ? 'active' : '' ?>">
        <div class="aff-loading" id="dash-loading">
            <div class="aff-spinner"></div>
        </div>

        <div id="dash-content" style="display:none">
            <!-- Welcome -->
            <div class="aff-welcome">
                <h2>Olá, <span id="dash-nome"></span></h2>
                <button class="aff-logout" onclick="doLogout()">Sair</button>
            </div>

            <!-- Stats -->
            <div class="aff-stats">
                <div class="aff-stat">
                    <div class="aff-stat-label">💰 Saldo Pendente</div>
                    <div class="aff-stat-value green" id="dash-pending">R$0,00</div>
                </div>
                <div class="aff-stat">
                    <div class="aff-stat-label">✅ Total Retirado</div>
                    <div class="aff-stat-value accent" id="dash-paid">R$0,00</div>
                </div>
                <div class="aff-stat">
                    <div class="aff-stat-label">📊 Total Vendas</div>
                    <div class="aff-stat-value" id="dash-vendas">0</div>
                </div>
                <div class="aff-stat">
                    <div class="aff-stat-label">📈 Comissão</div>
                    <div class="aff-stat-value yellow" id="dash-pct">10%</div>
                </div>
            </div>

            <!-- Link -->
            <div class="aff-link-card">
                <h3>🔗 Seu link de indicação</h3>
                <div class="aff-link-row">
                    <input type="text" id="dash-link" readonly>
                    <button class="aff-copy-btn" onclick="copyLink()">📋 Copiar</button>
                </div>
            </div>

            <!-- Vendas -->
            <div class="aff-section-title">📋 Últimas Vendas</div>
            <div class="aff-vendas-list" id="dash-vendas-list"></div>
        </div>
    </div>

</div>

<script>
var API = 'api.php';

// ─── Tabs ────────────────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.aff-tab').forEach(function(t, i) {
        t.classList.toggle('active', (tab === 'login' ? i === 0 : i === 1));
    });
    document.getElementById('form-login').classList.toggle('active', tab === 'login');
    document.getElementById('form-register').classList.toggle('active', tab === 'register');
    hideMsg();
}

// ─── Mensagens ───────────────────────────────────────
function showMsg(text, type) {
    var el = document.getElementById('auth-msg');
    el.textContent = text;
    el.className = 'aff-msg ' + type;
}
function hideMsg() {
    var el = document.getElementById('auth-msg');
    el.className = 'aff-msg';
    el.textContent = '';
}

// ─── Máscara de telefone ─────────────────────────────
function maskPhone(input) {
    var v = input.value.replace(/\D/g, '');
    if (v.length <= 10) {
        v = v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
    } else {
        v = v.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
    }
    input.value = v;
}

// ─── LOGIN ───────────────────────────────────────────
function doLogin(e) {
    e.preventDefault();
    var btn = document.getElementById('btn-login');
    var phone = document.getElementById('login-phone').value;
    btn.disabled = true;
    btn.textContent = 'Entrando...';
    hideMsg();

    ajax('POST', API + '?action=login', 'phone=' + encodeURIComponent(phone), function(r) {
        if (r.status === 'success') {
            showMsg(r.msg, 'success');
            setTimeout(function() {
                document.getElementById('auth-section').style.display = 'none';
                var dash = document.getElementById('dashboard-section');
                dash.classList.add('active');
                dash.style.display = '';
                loadDashboard();
            }, 600);
        } else {
            showMsg(r.msg || 'Erro ao entrar.', 'error');
            btn.disabled = false;
            btn.textContent = 'Entrar';
        }
    });
    return false;
}

// ─── CADASTRO ────────────────────────────────────────
function doRegister(e) {
    e.preventDefault();
    var btn = document.getElementById('btn-register');
    var firstname = document.getElementById('reg-firstname').value;
    var lastname = document.getElementById('reg-lastname').value;
    var phone = document.getElementById('reg-phone').value;
    btn.disabled = true;
    btn.textContent = 'Criando conta...';
    hideMsg();

    var body = 'firstname=' + encodeURIComponent(firstname)
             + '&lastname=' + encodeURIComponent(lastname)
             + '&phone=' + encodeURIComponent(phone);

    ajax('POST', API + '?action=register', body, function(r) {
        if (r.status === 'success') {
            showMsg(r.msg, 'success');
            setTimeout(function() {
                document.getElementById('auth-section').style.display = 'none';
                var dash = document.getElementById('dashboard-section');
                dash.classList.add('active');
                dash.style.display = '';
                loadDashboard();
            }, 600);
        } else {
            showMsg(r.msg || 'Erro no cadastro.', 'error');
            btn.disabled = false;
            btn.textContent = '🚀 Criar conta e ser Afiliado';
        }
    });
    return false;
}

// ─── DASHBOARD ───────────────────────────────────────
function loadDashboard() {
    document.getElementById('dash-loading').style.display = '';
    document.getElementById('dash-content').style.display = 'none';

    ajax('GET', API + '?action=dashboard', null, function(r) {
        document.getElementById('dash-loading').style.display = 'none';

        if (r.status !== 'success') {
            // Sessão expirou
            document.getElementById('dashboard-section').classList.remove('active');
            document.getElementById('auth-section').style.display = '';
            showMsg('Sessão expirada. Faça login novamente.', 'error');
            return;
        }

        document.getElementById('dash-content').style.display = '';
        document.getElementById('dash-nome').textContent = r.nome;
        document.getElementById('dash-pending').textContent = 'R$' + r.amount_pending;
        document.getElementById('dash-paid').textContent = 'R$' + r.amount_paid;
        document.getElementById('dash-vendas').textContent = r.total_vendas;
        document.getElementById('dash-pct').textContent = r.percentage + '%';
        document.getElementById('dash-link').value = r.link;

        // Renderizar vendas
        var list = document.getElementById('dash-vendas-list');
        list.innerHTML = '';

        if (!r.vendas || r.vendas.length === 0) {
            list.innerHTML = '<div class="aff-empty"><span>📭</span>Nenhuma venda registrada ainda.<br>Compartilhe seu link!</div>';
            return;
        }

        r.vendas.forEach(function(v) {
            var statusClass = v.status === 2 ? 'pago' : (v.status === 3 ? 'cancelado' : 'pendente');
            var statusText = v.status === 2 ? 'Pago' : (v.status === 3 ? 'Cancelado' : 'Pendente');

            list.innerHTML += '<div class="aff-venda-item">'
                + '<div class="aff-venda-info">'
                + '<h4>' + (v.produto || 'Pedido #' + v.id) + '</h4>'
                + '<p>' + v.cliente + ' · ' + v.data + '</p>'
                + '</div>'
                + '<div class="aff-venda-right">'
                + '<div class="aff-venda-valor">R$' + v.valor + '</div>'
                + '<span class="aff-badge ' + statusClass + '">' + statusText + '</span>'
                + '</div>'
                + '</div>';
        });
    });
}

// ─── LOGOUT ──────────────────────────────────────────
function doLogout() {
    ajax('GET', API + '?action=logout', null, function() {
        document.getElementById('dashboard-section').classList.remove('active');
        document.getElementById('dash-content').style.display = 'none';
        document.getElementById('auth-section').style.display = '';
        document.getElementById('btn-login').disabled = false;
        document.getElementById('btn-login').textContent = 'Entrar';
        document.getElementById('btn-register').disabled = false;
        document.getElementById('btn-register').textContent = '🚀 Criar conta e ser Afiliado';
        hideMsg();
    });
}

// ─── COPIAR LINK ─────────────────────────────────────
function copyLink() {
    var input = document.getElementById('dash-link');
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

// ─── AJAX helper (vanilla) ───────────────────────────
function ajax(method, url, body, cb) {
    var xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    if (method === 'POST') {
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    }
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            try {
                cb(JSON.parse(xhr.responseText));
            } catch(e) {
                cb({ status: 'failed', msg: 'Erro inesperado.' });
            }
        }
    };
    xhr.send(body);
}

// ─── Auto-load dashboard se logado ───────────────────
<?php if ($logged): ?>
loadDashboard();
<?php endif; ?>
</script>

</body>
</html>
