<?php

require_once './settings.php';

$is_logged   = (bool) $_settings->userdata('id');
$is_affiliate = $_settings->userdata('is_affiliate') == 1;

// Se já é afiliado, manda direto para a área do afiliado
if ($is_logged && $is_affiliate) {
    echo '<script>location.replace(\'/user/afiliado\');</script>';
    exit();
}

$firstname = $is_logged ? $_settings->userdata('firstname') : '';
$lastname  = $is_logged ? $_settings->userdata('lastname')  : '';
$phone     = $is_logged ? formatPhoneNumber($_settings->userdata('phone')) : '';
?>
<style>
.aff-req-wrap {
    max-width: 480px;
    margin: 40px auto;
    padding: 0 16px;
}
.aff-req-card {
    background: rgb(36, 39, 49);
    border-radius: 24px;
    padding: 36px 32px;
    text-align: center;
}
.aff-req-icon { font-size: 52px; margin-bottom: 16px; }
.aff-req-card h2 {
    font-size: 22px; font-weight: 700;
    margin: 0 0 8px; color: #fff;
}
.aff-req-card > p {
    font-size: 14px; color: #8081a0;
    margin: 0 0 28px; line-height: 1.6;
}
.aff-field { text-align: left; margin-bottom: 16px; }
.aff-field label {
    display: block; font-size: 11px; font-weight: 600;
    color: #8081a0; text-transform: uppercase;
    letter-spacing: 0.5px; margin-bottom: 6px;
}
.aff-field input {
    width: 100%;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 10px; padding: 11px 14px;
    font-size: 14px; color: #fff;
    box-sizing: border-box; outline: none;
    transition: border-color .2s;
}
.aff-field input:focus { border-color: rgba(208,128,255,0.5); }
.aff-field input[disabled] { color: #aaa; cursor: not-allowed; }
.aff-commission-info {
    background: rgba(53,93,255,0.12);
    border: 1px solid rgba(53,93,255,0.25);
    border-radius: 12px; padding: 14px 16px;
    margin-bottom: 24px;
    display: flex; align-items: center; justify-content: space-between;
}
.aff-commission-info span { font-size: 13px; color: #8081a0; }
.aff-commission-info strong { font-size: 18px; color: #4fbf67; font-weight: 700; }
.btn-become-aff {
    width: 100%;
    background: linear-gradient(135deg, #d080ff 0%, #6c5dd3 100%);
    color: #fff; border: none; border-radius: 14px;
    padding: 15px; font-size: 15px; font-weight: 700;
    cursor: pointer; transition: opacity 0.2s; letter-spacing: 0.3px;
}
.btn-become-aff:hover { opacity: 0.9; }
.btn-become-aff:disabled { opacity: 0.5; cursor: not-allowed; }
.aff-terms { font-size: 11px; color: #8081a0; margin-top: 14px; line-height: 1.5; }
.aff-divider {
    font-size: 11px; color: #555; margin: 20px 0 16px;
    display: flex; align-items: center; gap: 8px;
}
.aff-divider::before, .aff-divider::after {
    content: ''; flex: 1;
    height: 1px; background: rgba(255,255,255,0.08);
}
</style>

<main>
<div class="container app-main">
<div class="aff-req-wrap">
    <div class="aff-req-card">
        <div class="aff-req-icon">🤝</div>
        <h2>Quero ser Afiliado</h2>
        <p>Divulgue nossas campanhas e ganhe comissão em cada venda feita pelo seu link!</p>

        <?php if (!$is_logged): ?>
        <!-- DESLOGADO: campos editáveis para criar conta + virar afiliado -->
        <div class="aff-field">
            <label>Nome</label>
            <input type="text" id="aff-firstname" placeholder="Seu primeiro nome" required>
        </div>
        <div class="aff-field">
            <label>Sobrenome</label>
            <input type="text" id="aff-lastname" placeholder="Seu sobrenome" required>
        </div>
        <div class="aff-field">
            <label>Telefone (WhatsApp)</label>
            <input type="tel" id="aff-phone" placeholder="(00) 00000-0000"
                   oninput="formatTelAff(this)" maxlength="15" required>
        </div>
        <div class="aff-divider">Sua comissão</div>

        <?php else: ?>
        <!-- LOGADO: campos bloqueados -->
        <div class="aff-field">
            <label>Nome</label>
            <input type="text" value="<?= htmlspecialchars($firstname . ' ' . $lastname) ?>" disabled>
        </div>
        <div class="aff-field">
            <label>Telefone</label>
            <input type="text" value="<?= htmlspecialchars($phone) ?>" disabled>
        </div>
        <?php endif; ?>

        <div class="aff-commission-info">
            <span>Sua comissão por venda</span>
            <strong>50%</strong>
        </div>

        <button class="btn-become-aff" id="btn-become" onclick="submitAffiliate()">
            🚀 Quero ser Afiliado!
        </button>
        <p class="aff-terms">
            Ao se cadastrar, você receberá seu link personalizado e passará a aparecer no painel de afiliados.
        </p>
    </div>
</div>
</div>

<script>
var _isLogged = <?= $is_logged ? 'true' : 'false' ?>;

function formatTelAff(input) {
    var v = input.value.replace(/\D/g, '');
    if (v.length <= 10) {
        v = v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
    } else {
        v = v.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
    }
    input.value = v;
}

function submitAffiliate() {
    var btn = document.getElementById('btn-become');
    btn.disabled = true;
    btn.textContent = 'Processando...';

    var data = {};

    if (!_isLogged) {
        var firstname = document.getElementById('aff-firstname').value.trim();
        var lastname  = document.getElementById('aff-lastname').value.trim();
        var phone     = document.getElementById('aff-phone').value.trim();

        if (!firstname || !lastname || !phone) {
            alert('Por favor, preencha todos os campos.');
            btn.disabled = false;
            btn.textContent = '🚀 Quero ser Afiliado!';
            return;
        }
        if (phone.replace(/\D/g,'').length < 10) {
            alert('Telefone inválido.');
            btn.disabled = false;
            btn.textContent = '🚀 Quero ser Afiliado!';
            return;
        }
        data = { firstname: firstname, lastname: lastname, phone: phone };
    }

    var action = _isLogged ? 'become_affiliate' : 'register_and_become_affiliate';

    $.ajax({
        url: _base_url_ + 'class/Main.php?action=' + action,
        method: 'POST',
        data: data,
        success: function(resp) {
            try {
                var r = JSON.parse(resp);
                if (r.status === 'success') {
                    btn.textContent = '✅ Cadastrado com sucesso!';
                    setTimeout(function() { location.href = '/user/afiliado'; }, 1500);
                } else {
                    alert(r.msg || 'Erro ao processar. Tente novamente.');
                    btn.disabled = false;
                    btn.textContent = '🚀 Quero ser Afiliado!';
                }
            } catch(e) {
                alert('Erro inesperado. Tente novamente.');
                btn.disabled = false;
                btn.textContent = '🚀 Quero ser Afiliado!';
            }
        },
        error: function() {
            alert('Erro de conexão. Tente novamente.');
            btn.disabled = false;
            btn.textContent = '🚀 Quero ser Afiliado!';
        }
    });
}
</script>
</main>
