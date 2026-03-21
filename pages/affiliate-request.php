<?php

require_once './settings.php';

// Precisa estar logado
if (!$_settings->userdata('id')) {
    echo '<script>alert(\'Faça login para continuar\'); location.replace(\'/\');</script>';
    exit();
}

// Se já é afiliado, manda para a área do afiliado
if ($_settings->userdata('is_affiliate') == 1) {
    echo '<script>location.replace(\'/user/afiliado\');</script>';
    exit();
}

$affiliate_id = $_settings->userdata('id');
$firstname    = $_settings->userdata('firstname');
$lastname     = $_settings->userdata('lastname');
$phone        = $_settings->userdata('phone');
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
.aff-req-icon {
    font-size: 52px;
    margin-bottom: 16px;
}
.aff-req-card h2 {
    font-size: 22px;
    font-weight: 700;
    margin: 0 0 8px;
    color: #fff;
}
.aff-req-card p {
    font-size: 14px;
    color: #8081a0;
    margin: 0 0 28px;
    line-height: 1.6;
}
.aff-field {
    text-align: left;
    margin-bottom: 16px;
}
.aff-field label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #8081a0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}
.aff-field input {
    width: 100%;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 10px;
    padding: 11px 14px;
    font-size: 14px;
    color: #aaa;
    cursor: not-allowed;
    box-sizing: border-box;
}
.aff-commission-info {
    background: rgba(53,93,255,0.12);
    border: 1px solid rgba(53,93,255,0.25);
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.aff-commission-info span { font-size: 13px; color: #8081a0; }
.aff-commission-info strong { font-size: 18px; color: #4fbf67; font-weight: 700; }
.btn-become-aff {
    width: 100%;
    background: linear-gradient(135deg, #d080ff 0%, #6c5dd3 100%);
    color: #fff;
    border: none;
    border-radius: 14px;
    padding: 15px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity 0.2s;
    letter-spacing: 0.3px;
}
.btn-become-aff:hover { opacity: 0.9; }
.btn-become-aff:disabled { opacity: 0.5; cursor: not-allowed; }
.aff-terms {
    font-size: 11px;
    color: #8081a0;
    margin-top: 14px;
    line-height: 1.5;
}
</style>

<main>
<div class="container app-main">
<div class="aff-req-wrap">
    <div class="aff-req-card">
        <div class="aff-req-icon">🤝</div>
        <h2>Quero ser Afiliado</h2>
        <p>Divulgue nossas campanhas e ganhe comissão em cada venda feita pelo seu link!</p>

        <div class="aff-field">
            <label>Nome</label>
            <input type="text" value="<?= htmlspecialchars($firstname . ' ' . $lastname) ?>" disabled>
        </div>
        <div class="aff-field">
            <label>Telefone</label>
            <input type="text" value="<?= htmlspecialchars(formatPhoneNumber($phone)) ?>" disabled>
        </div>

        <div class="aff-commission-info">
            <span>Sua comissão por venda</span>
            <strong>50%</strong>
        </div>

        <button class="btn-become-aff" id="btn-become" onclick="becomeAffiliate()">
            🚀 Quero ser Afiliado!
        </button>
        <p class="aff-terms">
            Ao se cadastrar, você receberá seu link personalizado e passará a aparecer no painel de afiliados.
        </p>
    </div>
</div>
</div>

<script>
function becomeAffiliate() {
    var btn = document.getElementById('btn-become');
    btn.disabled = true;
    btn.textContent = 'Processando...';

    $.ajax({
        url: _base_url_ + 'class/Main.php?action=become_affiliate',
        method: 'POST',
        data: {},
        success: function(resp) {
            try {
                var r = JSON.parse(resp);
                if (r.status === 'success') {
                    btn.textContent = '✅ Cadastrado com sucesso!';
                    setTimeout(function() {
                        location.href = '/user/afiliado';
                    }, 1500);
                } else {
                    alert(r.msg || 'Erro ao processar solicitação.');
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
