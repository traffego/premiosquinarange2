<?php

require_once './settings.php';

if (!$_settings->userdata('is_affiliate')) {
    echo '<script>alert(\'Você não tem permissão para acessar essa página\'); ' . "\r\n" . '    location.replace(\'/\');</script>';
    exit();
}

if ($_settings->userdata('id') != '') {
    $qry = $conn->query('SELECT * FROM `customer_list` where id = \'' . $_settings->userdata('id') . '\'');
    if (0 < $qry->num_rows) {
        foreach ($qry->fetch_array() as $k => $v) {
            if (!is_numeric($k)) {
                $$k = $v;
            }
        }
    }
} else {
    echo '<script>alert(\'Você não tem permissão para acessar essa página\'); ' . "\r\n" . '    location.replace(\'/\');</script>';
    exit();
}

$affiliate_id = $_settings->userdata('id');

// Busca referral code do afiliado logado
$qryRef = $conn->query("SELECT * FROM referral WHERE customer_id = '$affiliate_id' LIMIT 1");
$referral_code = '';
$amount_paid = 0;
$amount_pending = 0;
if ($qryRef && $qryRef->num_rows > 0) {
    $rowRef = $qryRef->fetch_assoc();
    $referral_code = $rowRef['referral_code'];
    $amount_paid = $rowRef['amount_paid'];
    $amount_pending = $rowRef['amount_pending'];
    $commission_pct = $rowRef['percentage'];
}

// Total de indicações (pedidos associados a esse referral_code)
$qryCount = $conn->query("SELECT COUNT(id) as total FROM order_list WHERE referral_id = '$referral_code'");
$quantity = 0;
if ($qryCount && $qryCount->num_rows > 0) {
    $quantity = $qryCount->fetch_assoc()['total'];
}

// Filtros GET
$filter_product = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$filter_status  = isset($_GET['status'])     ? intval($_GET['status'])     : -1;

// Lista de produtos que têm pedidos desse afiliado (para o filtro de rifa)
$qryProducts = $conn->query("
    SELECT DISTINCT p.id, p.name
    FROM product_list p
    INNER JOIN order_list o ON o.product_id = p.id
    WHERE o.referral_id = '$referral_code'
    ORDER BY p.name ASC
");

// Monta WHERE dinâmico
$where = "o.referral_id = '$referral_code'";
if ($filter_product > 0) {
    $where .= " AND o.product_id = $filter_product";
}
if ($filter_status >= 0) {
    $where .= " AND o.status = $filter_status";
}

// Query principal das vendas filtradas
$qryOrders = $conn->query("
    SELECT o.id, o.product_name, o.total_amount, o.status, o.date_created, r.percentage
    FROM order_list o
    INNER JOIN referral r ON o.referral_id = r.referral_code
    WHERE $where
    ORDER BY o.date_created DESC
");

// Soma total de comissão com base nos filtros
$qryTotal = $conn->query("
    SELECT SUM(o.total_amount * r.percentage / 100) as total_commission
    FROM order_list o
    INNER JOIN referral r ON o.referral_id = r.referral_code
    WHERE $where AND o.status = 2
");
$total_commission = 0;
if ($qryTotal && $qryTotal->num_rows > 0) {
    $total_commission = $qryTotal->fetch_assoc()['total_commission'] ?? 0;
}
?>
<style>
    .aff-page { max-width: 960px; margin: 0 auto; padding: 0 12px; }

    /* Cards de saldo */
    .aff-hero {
        background: rgb(36, 39, 49);
        border-radius: 24px;
        padding: 24px;
        margin-bottom: 24px;
    }
    .aff-profile { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
    .aff-avatar img { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; }
    .aff-name { font-size: 18px; font-weight: 600; margin: 0; }
    .aff-phone { font-size: 13px; color: #8081a0; margin: 0; }

    .aff-stats { display: flex; gap: 12px; flex-wrap: wrap; }
    .aff-stat {
        flex: 1; min-width: 130px;
        border: 1px solid rgba(228,228,228,0.12);
        border-radius: 16px;
        padding: 16px;
        text-align: center;
    }
    .aff-stat label { font-size: 12px; color: #8081a0; display: block; margin-bottom: 6px; }
    .aff-stat .val { font-size: 20px; font-weight: 700; color: #4fbf67; }
    .aff-stat .val.blue { color: #355dff; }
    .aff-stat .val.white { color: #fff; }

    /* Link de afiliado */
    .aff-link-box {
        position: relative;
        margin-top: 20px;
        background: radial-gradient(103.03% 103.03% at 0% 0%, #d080ff 0%, #6c5dd3 100%);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
    }
    .aff-link-box h3 { margin: 0 0 6px; font-size: 16px; font-weight: 600; }
    .aff-link-box p  { font-size: 12px; color: rgba(255,255,255,0.75); margin: 0 0 12px; }
    .aff-link-input-wrap { display: flex; }
    .aff-link-input-wrap input {
        flex: 1;
        background: rgba(255,255,255,0.15);
        border: none;
        border-radius: 8px 0 0 8px;
        padding: 10px 12px;
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        outline: none;
    }
    .aff-link-input-wrap button {
        background: #fff;
        border: none;
        border-radius: 0 8px 8px 0;
        padding: 0 14px;
        cursor: pointer;
        color: #6c5dd3;
        font-size: 13px;
        font-weight: 700;
        transition: background 0.2s;
    }
    .aff-link-input-wrap button:hover { background: #ede9ff; }

    /* Filtros */
    .aff-filters {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: flex-end;
        margin-bottom: 20px;
    }
    .aff-filters label { font-size: 11px; color: #8081a0; display: block; margin-bottom: 4px; }
    .aff-filters select {
        background: #1e2130;
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 10px;
        color: #fff;
        padding: 9px 14px;
        font-size: 13px;
        cursor: pointer;
        outline: none;
    }
    .aff-filters button {
        background: #355dff;
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 9px 20px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        margin-top: 18px;
    }
    .aff-filters button:hover { background: #2247e0; }
    .aff-filters a.btn-clear {
        display: inline-block;
        background: rgba(255,255,255,0.07);
        color: #aaa;
        border-radius: 10px;
        padding: 9px 16px;
        font-size: 13px;
        text-decoration: none;
        margin-top: 18px;
        transition: background 0.2s;
    }
    .aff-filters a.btn-clear:hover { background: rgba(255,255,255,0.13); }

    /* Resumo comissão filtrada */
    .aff-commission-bar {
        background: rgba(53,93,255,0.12);
        border: 1px solid rgba(53,93,255,0.3);
        border-radius: 12px;
        padding: 12px 18px;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
    }
    .aff-commission-bar span { font-size: 13px; color: #8081a0; }
    .aff-commission-bar strong { font-size: 18px; color: #4fbf67; }

    /* Tabela */
    .aff-table-wrap {
        border: 1px solid rgba(228,228,228,0.12);
        border-radius: 16px;
        overflow: hidden;
    }
    .aff-table { width: 100%; border-collapse: collapse; }
    .aff-table thead th {
        background: rgba(255,255,255,0.04);
        padding: 12px 14px;
        font-size: 11px;
        font-weight: 600;
        color: #8081a0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: left;
        border-bottom: 1px solid rgba(228,228,228,0.1);
    }
    .aff-table tbody td {
        padding: 12px 14px;
        font-size: 13px;
        border-bottom: 1px solid rgba(228,228,228,0.07);
        vertical-align: middle;
    }
    .aff-table tbody tr:last-child td { border-bottom: none; }
    .aff-table tbody tr:hover td { background: rgba(255,255,255,0.02); }

    .badge-pending  { background: rgba(255,159,56,0.15); color: #ff9f38; border-radius: 6px; padding: 3px 8px; font-size: 11px; font-weight: 600; }
    .badge-approved { background: rgba(79,191,103,0.15); color: #4fbf67; border-radius: 6px; padding: 3px 8px; font-size: 11px; font-weight: 600; }
    .badge-other    { background: rgba(255,255,255,0.08); color: #aaa;    border-radius: 6px; padding: 3px 8px; font-size: 11px; font-weight: 600; }

    .empty-state { text-align: center; padding: 48px 24px; color: #8081a0; }
    .empty-state svg { display: block; margin: 0 auto 12px; opacity: 0.35; }
    .empty-state p { margin: 0; font-size: 14px; }
</style>

<main>
<div class="container app-main">
<div class="aff-page">

    <!-- Header do perfil + stats -->
    <div class="aff-hero">
        <div class="aff-profile">
            <div class="aff-avatar">
                <img src="<?php echo validate_image($avatar); ?>" alt="Avatar">
            </div>
            <div>
                <p class="aff-name"><?php echo htmlspecialchars($firstname . ' ' . $lastname); ?></p>
                <p class="aff-phone"><?php echo formatPhoneNumber($phone); ?></p>
            </div>
        </div>

        <div class="aff-stats">
            <div class="aff-stat">
                <label>Retirado</label>
                <div class="val">R$<?= number_format($amount_paid, 2, ',', '.') ?></div>
            </div>
            <div class="aff-stat">
                <label>Saldo Pendente</label>
                <div class="val blue">R$<?= number_format($amount_pending, 2, ',', '.') ?></div>
            </div>
            <div class="aff-stat">
                <label>Indicações</label>
                <div class="val white"><?= $quantity ?></div>
            </div>
            <div class="aff-stat">
                <label>Comissão (%)</label>
                <div class="val white"><?= isset($commission_pct) ? $commission_pct . '%' : '-' ?></div>
            </div>
        </div>
    </div>

    <!-- Link de afiliado -->
    <div class="aff-link-box">
        <h3>Convide seus amigos e ganhe por cada venda!</h3>
        <p>Compartilhe seu link de afiliado</p>
        <div class="aff-link-input-wrap">
            <input id="affiliate_url" type="text" readonly
                   value="<?php echo BASE_REF . '?&ref=' . $referral_code; ?>">
            <button id="btn-copy" onclick="copyAffLink()">📋 Copiar</button>
        </div>
    </div>

    <!-- Seção de vendas -->
    <div class="app-title" style="margin-bottom:16px;">
        <h1 style="font-size:18px; margin:0;">Minhas Vendas</h1>
    </div>

    <!-- Filtros -->
    <form method="GET" action="">
        <div class="aff-filters">
            <div>
                <label>Filtrar por rifa</label>
                <select name="product_id">
                    <option value="0">Todas as rifas</option>
                    <?php if ($qryProducts && $qryProducts->num_rows > 0):
                        $qryProducts->data_seek(0);
                        while ($p = $qryProducts->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>" <?= $filter_product == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?>
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
            <div>
                <label>Status</label>
                <select name="status">
                    <option value="-1" <?= $filter_status == -1 ? 'selected' : '' ?>>Todos</option>
                    <option value="1"  <?= $filter_status == 1  ? 'selected' : '' ?>>Pendente</option>
                    <option value="2"  <?= $filter_status == 2  ? 'selected' : '' ?>>Aprovado</option>
                </select>
            </div>
            <button type="submit">Filtrar</button>
            <a href="?" class="btn-clear">Limpar</a>
        </div>
    </form>

    <!-- Barra de comissão total aprovada (filtrada) -->
    <div class="aff-commission-bar">
        <span>Comissão total aprovada <?= ($filter_product > 0 || $filter_status >= 0) ? '(filtro ativo)' : '' ?></span>
        <strong>R$<?= number_format($total_commission, 2, ',', '.') ?></strong>
    </div>

    <!-- Tabela de vendas -->
    <div class="aff-table-wrap">
        <?php if ($qryOrders && $qryOrders->num_rows > 0): ?>
        <table class="aff-table">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Valor Venda</th>
                    <th>Comissão</th>
                    <th>Data</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $qryOrders->fetch_assoc()):
                    $commission = ($row['total_amount'] * $row['percentage']) / 100;
                    if ($row['status'] == 1) {
                        $badge = '<span class="badge-pending">Pendente</span>';
                    } elseif ($row['status'] == 2) {
                        $badge = '<span class="badge-approved">Aprovado</span>';
                    } else {
                        $badge = '<span class="badge-other">Outro</span>';
                    }
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                    <td>R$<?= number_format($row['total_amount'], 2, ',', '.') ?></td>
                    <td>R$<?= number_format($commission, 2, ',', '.') ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['date_created'])) ?></td>
                    <td><?= $badge ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#fff" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.553.553 0 0 1-1.1 0L7.1 4.995z"/>
            </svg>
            <p>Nenhuma venda encontrada<?= ($filter_product > 0 || $filter_status >= 0) ? ' com os filtros selecionados' : '' ?>.</p>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /aff-page -->
</div><!-- /container -->

<script>
function copyAffLink() {
    var input = document.getElementById('affiliate_url');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(function() {
        var btn = document.getElementById('btn-copy');
        btn.textContent = '✅ Copiado!';
        setTimeout(function() { btn.textContent = '📋 Copiar'; }, 2000);
    }).catch(function() {
        document.execCommand('copy');
        alert('Link copiado!');
    });
}
</script>
</main>
