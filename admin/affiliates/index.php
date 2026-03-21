<?php
// Nova index de afiliados - Dashboard completo

// ------------------------------------------------------------------
// 1. Dados dos cards de topo (totais gerais)
// ------------------------------------------------------------------
$qAtivos   = $conn->query("SELECT COUNT(id) as total FROM referral WHERE status = 1")->fetch_assoc();
$totalAtivos = $qAtivos['total'] ?? 0;

$qFat = $conn->query("SELECT SUM(o.total_amount) as total FROM order_list o WHERE o.status = 2 AND o.referral_id IS NOT NULL")->fetch_assoc();
$totalFat = $qFat['total'] ?? 0;

$qPend = $conn->query("SELECT SUM(amount_pending) as total FROM referral")->fetch_assoc();
$totalPend = $qPend['total'] ?? 0;

$qPago = $conn->query("SELECT SUM(amount_paid) as total FROM referral")->fetch_assoc();
$totalPago = $qPago['total'] ?? 0;

// ------------------------------------------------------------------
// 2. Rankings: Diamante (1º), Ouro (2º), Prata (3º)
//    Base: somatório de vendas pagas (status=2) por afiliado
// ------------------------------------------------------------------
$sqlRanking = "
    SELECT
        r.id              AS referral_id,
        r.referral_code,
        r.percentage,
        r.amount_pending,
        r.amount_paid,
        c.firstname,
        c.lastname,
        c.phone,
        COALESCE(SUM(CASE WHEN o.status = 2 THEN o.total_amount ELSE 0 END), 0) AS total_vendas,
        COUNT(DISTINCT CASE WHEN o.status = 2 THEN o.id ELSE NULL END) AS qtd_vendas
    FROM referral r
    INNER JOIN customer_list c ON c.id = r.customer_id
    LEFT JOIN order_list o ON o.referral_id = r.referral_code
    WHERE r.status = 1
    GROUP BY r.id, c.firstname, c.lastname, c.phone, r.referral_code, r.percentage, r.amount_pending, r.amount_paid
    ORDER BY total_vendas DESC
";
$rankingResult = $conn->query($sqlRanking);
$ranking = [];
while ($row = $rankingResult->fetch_assoc()) {
    $ranking[] = $row;
}
$diamante = $ranking[0] ?? null;
$ouro     = $ranking[1] ?? null;
$prata    = $ranking[2] ?? null;

// ------------------------------------------------------------------
// 3. Filtro por sorteio (product)
// ------------------------------------------------------------------
$sorteioFiltro = isset($_GET['sorteio']) ? intval($_GET['sorteio']) : 0;
$products = $conn->query("SELECT id, name FROM product_list ORDER BY id DESC");

// ------------------------------------------------------------------
// 4. Listagem completa de afiliados com vendas por sorteio
// ------------------------------------------------------------------
$perPage = 20;
$page    = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$offset  = $perPage * ($page - 1);

$whereProduct = $sorteioFiltro > 0 ? "AND o.product_id = $sorteioFiltro" : "";

$sqlCount = "
    SELECT COUNT(DISTINCT r.id) as total
    FROM referral r
    INNER JOIN customer_list c ON c.id = r.customer_id
    LEFT JOIN order_list o ON o.referral_id = r.referral_code AND o.status = 2 $whereProduct
";
$totalResults = $conn->query($sqlCount)->fetch_assoc()['total'] ?? 0;
$totalPages   = max(1, ceil($totalResults / $perPage));

$sqlList = "
    SELECT
        r.id              AS referral_id,
        r.referral_code,
        r.percentage,
        r.amount_pending,
        r.amount_paid,
        r.status,
        c.firstname,
        c.lastname,
        c.phone,
        COALESCE(SUM(CASE WHEN o.status = 2 THEN o.total_amount ELSE 0 END), 0) AS total_vendas,
        COUNT(DISTINCT CASE WHEN o.status = 2 THEN o.id ELSE NULL END)           AS qtd_vendas
    FROM referral r
    INNER JOIN customer_list c ON c.id = r.customer_id
    LEFT JOIN order_list o ON o.referral_id = r.referral_code AND o.status = 2 $whereProduct
    GROUP BY r.id, r.referral_code, r.percentage, r.amount_pending, r.amount_paid, r.status,
             c.firstname, c.lastname, c.phone
    ORDER BY total_vendas DESC
    LIMIT $perPage OFFSET $offset
";
$listResult = $conn->query($sqlList);

$BASE_REF = defined('BASE_REF') ? BASE_REF : '';
?>
<style>
/* ===== Reset / base ===== */
.aff-dash * { box-sizing: border-box; }
.aff-dash {
    font-family: 'Inter', 'Poppins', sans-serif;
    padding: 0 0 40px;
}

/* ===== Topo / header row ===== */
.aff-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 28px;
}
.aff-header h2 {
    font-size: 1.6rem;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0;
}
.dark .aff-header h2 { color: #e2e8f0; }
.aff-header-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-aff {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: 10px;
    font-size: .85rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all .18s;
    text-decoration: none;
}
.btn-purple  { background: #7c3aed; color: #fff; }
.btn-purple:hover { background: #6d28d9; }
.btn-teal    { background: #0d9488; color: #fff; }
.btn-teal:hover { background: #0f766e; }

/* ===== Cards de métricas gerais ===== */
.aff-stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
}
.aff-stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 22px 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    display: flex;
    align-items: center;
    gap: 16px;
}
.dark .aff-stat-card { background: #1e293b; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
.aff-stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.3rem;
}
.icon-purple { background: #ede9fe; color: #7c3aed; }
.icon-green  { background: #d1fae5; color: #065f46; }
.icon-amber  { background: #fef3c7; color: #92400e; }
.icon-teal   { background: #ccfbf1; color: #0f766e; }
.dark .icon-purple { background: #4c1d95; color: #a78bfa; }
.dark .icon-green  { background: #064e3b; color: #6ee7b7; }
.dark .icon-amber  { background: #78350f; color: #fde68a; }
.dark .icon-teal   { background: #0d3b38; color: #5eead4; }
.aff-stat-info label {
    font-size: .72rem;
    font-weight: 600;
    color: #6b7280;
    letter-spacing: .04em;
    text-transform: uppercase;
    display: block;
    margin-bottom: 4px;
}
.dark .aff-stat-info label { color: #94a3b8; }
.aff-stat-info strong {
    font-size: 1.25rem;
    font-weight: 700;
    color: #111827;
}
.dark .aff-stat-info strong { color: #f1f5f9; }

/* ===== Ranking Diamante/Ouro/Prata ===== */
.aff-ranking-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 16px;
}
.dark .aff-ranking-title { color: #e2e8f0; }
.aff-ranking-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 16px;
    margin-bottom: 36px;
}
.rank-card {
    border-radius: 18px;
    padding: 24px 22px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,.12);
    color: #fff;
}
.rank-card::before {
    content: '';
    position: absolute;
    top: -30px;
    right: -30px;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    background: rgba(255,255,255,.12);
}
.rank-diamond { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); }
.rank-gold    { background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); }
.rank-silver  { background: linear-gradient(135deg, #475569 0%, #94a3b8 100%); }
.rank-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    background: rgba(255,255,255,.2);
    border-radius: 20px;
    padding: 3px 12px;
    margin-bottom: 14px;
}
.rank-name {
    font-size: 1.05rem;
    font-weight: 700;
    margin-bottom: 2px;
}
.rank-phone {
    font-size: .8rem;
    opacity: .8;
    margin-bottom: 14px;
}
.rank-metrics {
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
}
.rank-metric label {
    display: block;
    font-size: .68rem;
    font-weight: 600;
    opacity: .75;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.rank-metric span {
    font-size: 1rem;
    font-weight: 700;
}
.rank-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: .6;
    font-size: .9rem;
    min-height: 100px;
}

/* ===== Filtro de sorteio ===== */
.aff-filter-bar {
    background: #fff;
    border-radius: 14px;
    padding: 16px 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}
.dark .aff-filter-bar { background: #1e293b; }
.aff-filter-bar label {
    font-size: .82rem;
    font-weight: 600;
    color: #374151;
    white-space: nowrap;
}
.dark .aff-filter-bar label { color: #cbd5e1; }
.aff-filter-bar select {
    flex: 1;
    min-width: 180px;
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    font-size: .85rem;
    background: #f9fafb;
    color: #111827;
    cursor: pointer;
    outline: none;
}
.dark .aff-filter-bar select {
    background: #0f172a;
    border-color: #334155;
    color: #f1f5f9;
}
.btn-sm {
    padding: 8px 16px;
    font-size: .82rem;
    border-radius: 8px;
}

/* ===== Tabela ===== */
.aff-table-wrap {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    overflow: hidden;
}
.dark .aff-table-wrap { background: #1e293b; }
.aff-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .84rem;
}
.aff-table thead tr {
    background: #f8fafc;
    text-transform: uppercase;
    font-size: .7rem;
    letter-spacing: .05em;
    color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
}
.dark .aff-table thead tr {
    background: #0f172a;
    color: #94a3b8;
    border-bottom: 1px solid #334155;
}
.aff-table th, .aff-table td {
    padding: 12px 16px;
    text-align: left;
    vertical-align: middle;
}
.aff-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background .12s;
}
.dark .aff-table tbody tr { border-bottom: 1px solid #1e2d3d; }
.aff-table tbody tr:hover { background: #f8fafc; }
.dark .aff-table tbody tr:hover { background: #0f172a; }
.aff-table td { color: #374151; }
.dark .aff-table td { color: #cbd5e1; }

/* Badge de status */
.badge-ativo   { background:#d1fae5; color:#065f46; border-radius:20px; padding:2px 10px; font-size:.72rem; font-weight:700; }
.badge-inativo { background:#fee2e2; color:#991b1b; border-radius:20px; padding:2px 10px; font-size:.72rem; font-weight:700; }
.dark .badge-ativo   { background:#064e3b; color:#6ee7b7; }
.dark .badge-inativo { background:#7f1d1d; color:#fca5a5; }

/* Botão copiar link */
.btn-copy-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 11px;
    border-radius: 8px;
    background: #ede9fe;
    color: #7c3aed;
    border: none;
    cursor: pointer;
    font-size: .75rem;
    font-weight: 600;
    transition: all .15s;
    white-space: nowrap;
}
.btn-copy-link:hover { background: #7c3aed; color: #fff; }
.dark .btn-copy-link { background: #4c1d95; color: #c4b5fd; }
.dark .btn-copy-link:hover { background: #7c3aed; color: #fff; }

/* Botões de ação (editar/deletar) */
.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all .15s;
}
.btn-edit   { background: #ede9fe; color: #7c3aed; }
.btn-delete { background: #fee2e2; color: #dc2626; }
.btn-edit:hover   { background: #7c3aed; color: #fff; }
.btn-delete:hover { background: #dc2626; color: #fff; }

/* Avatar inicial */
.aff-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #7c3aed, #4f46e5);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .82rem;
    font-weight: 700;
    flex-shrink: 0;
}

/* Célula nome + avatar */
.cell-name { display: flex; align-items: center; gap: 10px; }

/* valor destaque */
.val-green { color: #059669; font-weight: 700; }
.val-amber { color: #d97706; font-weight: 700; }
.dark .val-green { color: #34d399; }
.dark .val-amber { color: #fbbf24; }

/* Paginação */
.aff-pagination {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 14px 20px;
    gap: 4px;
    flex-wrap: wrap;
}
.pg-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #374151;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all .15s;
    padding: 0 8px;
}
.pg-btn:hover, .pg-btn.active { background: #7c3aed; color: #fff; border-color: #7c3aed; }
.dark .pg-btn { background: #0f172a; border-color: #334155; color: #cbd5e1; }
.dark .pg-btn:hover, .dark .pg-btn.active { background: #7c3aed; border-color: #7c3aed; color: #fff; }

/* Toast */
#aff-toast {
    position: fixed;
    bottom: 28px;
    right: 28px;
    background: #1e293b;
    color: #fff;
    padding: 12px 20px;
    border-radius: 12px;
    font-size: .85rem;
    font-weight: 600;
    box-shadow: 0 8px 30px rgba(0,0,0,.3);
    opacity: 0;
    transform: translateY(10px);
    transition: all .25s;
    pointer-events: none;
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 8px;
}
#aff-toast.show { opacity: 1; transform: translateY(0); }

/* Responsivo */
@media (max-width: 768px) {
    .aff-table th:nth-child(4),
    .aff-table td:nth-child(4),
    .aff-table th:nth-child(5),
    .aff-table td:nth-child(5) { display: none; }
}
</style>

<div class="aff-dash">

    <!-- ===== Cabeçalho ===== -->
    <div class="aff-header">
        <h2>📊 Painel de Afiliados</h2>
        <div class="aff-header-actions">
            <a href="./?page=affiliates/create_affiliate" class="btn-aff btn-purple">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/></svg>
                Cadastrar afiliado
            </a>
            <a href="./?page=affiliates/sales" class="btn-aff" style="background:#0284c7;color:#fff;" onmouseover="this.style.background='#0369a1'" onmouseout="this.style.background='#0284c7'">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                Ver vendas
            </a>
            <a href="./?page=affiliates/create_payment" class="btn-aff btn-teal">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM2 9v5a2 2 0 002 2h12a2 2 0 002-2V9H2z"/></svg>
                Registrar pagamento
            </a>
        </div>
    </div>

    <!-- ===== Cards de métricas gerais ===== -->
    <div class="aff-stat-grid">
        <div class="aff-stat-card">
            <div class="aff-stat-icon icon-purple">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/></svg>
            </div>
            <div class="aff-stat-info">
                <label>Afiliados Ativos</label>
                <strong><?= number_format($totalAtivos) ?></strong>
            </div>
        </div>
        <div class="aff-stat-card">
            <div class="aff-stat-icon icon-green">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.469.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.028 2.353 1.118V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.028-2.354-1.118V5z" clip-rule="evenodd"/></svg>
            </div>
            <div class="aff-stat-info">
                <label>Faturamento Gerado</label>
                <strong>R$ <?= number_format($totalFat, 2, ',', '.') ?></strong>
            </div>
        </div>
        <div class="aff-stat-card">
            <div class="aff-stat-icon icon-amber">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/></svg>
            </div>
            <div class="aff-stat-info">
                <label>Saldo Pendente</label>
                <strong>R$ <?= number_format($totalPend, 2, ',', '.') ?></strong>
            </div>
        </div>
        <div class="aff-stat-card">
            <div class="aff-stat-icon icon-teal">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM2 9v5a2 2 0 002 2h12a2 2 0 002-2V9H2z"/></svg>
            </div>
            <div class="aff-stat-info">
                <label>Total Pago</label>
                <strong>R$ <?= number_format($totalPago, 2, ',', '.') ?></strong>
            </div>
        </div>
    </div>

    <!-- ===== Ranking Diamante / Ouro / Prata ===== -->
    <div class="aff-ranking-title">🏆 Top Afiliados</div>
    <div class="aff-ranking-grid">

        <!-- DIAMANTE -->
        <div class="rank-card rank-diamond">
            <?php if ($diamante): ?>
            <div class="rank-badge">💎 Diamante</div>
            <div class="rank-name"><?= htmlspecialchars($diamante['firstname'] . ' ' . $diamante['lastname']) ?></div>
            <div class="rank-phone"><?= formatPhoneNumber($diamante['phone']) ?></div>
            <div class="rank-metrics">
                <div class="rank-metric">
                    <label>Vendas pagas</label>
                    <span><?= number_format($diamante['qtd_vendas']) ?></span>
                </div>
                <div class="rank-metric">
                    <label>Faturado</label>
                    <span>R$ <?= number_format($diamante['total_vendas'], 2, ',', '.') ?></span>
                </div>
                <div class="rank-metric">
                    <label>Comissão</label>
                    <span><?= $diamante['percentage'] ?>%</span>
                </div>
            </div>
            <?php else: ?>
            <div class="rank-empty">💎 Nenhum afiliado ainda</div>
            <?php endif; ?>
        </div>

        <!-- OURO -->
        <div class="rank-card rank-gold">
            <?php if ($ouro): ?>
            <div class="rank-badge">🥇 Ouro</div>
            <div class="rank-name"><?= htmlspecialchars($ouro['firstname'] . ' ' . $ouro['lastname']) ?></div>
            <div class="rank-phone"><?= formatPhoneNumber($ouro['phone']) ?></div>
            <div class="rank-metrics">
                <div class="rank-metric">
                    <label>Vendas pagas</label>
                    <span><?= number_format($ouro['qtd_vendas']) ?></span>
                </div>
                <div class="rank-metric">
                    <label>Faturado</label>
                    <span>R$ <?= number_format($ouro['total_vendas'], 2, ',', '.') ?></span>
                </div>
                <div class="rank-metric">
                    <label>Comissão</label>
                    <span><?= $ouro['percentage'] ?>%</span>
                </div>
            </div>
            <?php else: ?>
            <div class="rank-empty">🥇 Nenhum afiliado ainda</div>
            <?php endif; ?>
        </div>

        <!-- PRATA -->
        <div class="rank-card rank-silver">
            <?php if ($prata): ?>
            <div class="rank-badge">🥈 Prata</div>
            <div class="rank-name"><?= htmlspecialchars($prata['firstname'] . ' ' . $prata['lastname']) ?></div>
            <div class="rank-phone"><?= formatPhoneNumber($prata['phone']) ?></div>
            <div class="rank-metrics">
                <div class="rank-metric">
                    <label>Vendas pagas</label>
                    <span><?= number_format($prata['qtd_vendas']) ?></span>
                </div>
                <div class="rank-metric">
                    <label>Faturado</label>
                    <span>R$ <?= number_format($prata['total_vendas'], 2, ',', '.') ?></span>
                </div>
                <div class="rank-metric">
                    <label>Comissão</label>
                    <span><?= $prata['percentage'] ?>%</span>
                </div>
            </div>
            <?php else: ?>
            <div class="rank-empty">🥈 Nenhum afiliado ainda</div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ===== Filtro por sorteio ===== -->
    <div class="aff-filter-bar">
        <label>🔍 Filtrar vendas por sorteio:</label>
        <form method="GET" style="display:flex;gap:10px;flex:1;flex-wrap:wrap;align-items:center;">
            <input type="hidden" name="page" value="affiliates">
            <select name="sorteio">
                <option value="0">Todos os sorteios</option>
                <?php
                while ($p = $products->fetch_assoc()):
                    $sel = ($sorteioFiltro == $p['id']) ? 'selected' : '';
                ?>
                <option value="<?= $p['id'] ?>" <?= $sel ?>><?= htmlspecialchars($p['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn-aff btn-purple btn-sm">Filtrar</button>
            <?php if ($sorteioFiltro > 0): ?>
            <a href="./?page=affiliates" class="btn-aff btn-sm" style="background:#f3f4f6;color:#374151;">✕ Limpar</a>
            <?php endif; ?>
        </form>
        <?php if ($sorteioFiltro > 0): ?>
        <span style="font-size:.78rem;color:#7c3aed;font-weight:600;white-space:nowrap;">
            📌 Exibindo apenas vendas do sorteio selecionado
        </span>
        <?php endif; ?>
    </div>

    <!-- ===== Tabela de afiliados ===== -->
    <div class="aff-table-wrap">
        <table class="aff-table">
            <thead>
                <tr>
                    <th>Afiliado</th>
                    <th>Telefone</th>
                    <th>Vendas pagas</th>
                    <th>Faturado</th>
                    <th>Saldo</th>
                    <th>Comissão</th>
                    <th>Status</th>
                    <th>Link</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($listResult && $listResult->num_rows > 0):
                while ($row = $listResult->fetch_assoc()):
                    $initials = strtoupper(substr($row['firstname'], 0, 1) . substr($row['lastname'], 0, 1));
                    $linkAff  = $BASE_REF . '?&ref=' . $row['referral_code'];
            ?>
                <tr>
                    <td>
                        <div class="cell-name">
                            <div class="aff-avatar"><?= $initials ?></div>
                            <span><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></span>
                        </div>
                    </td>
                    <td><?= formatPhoneNumber($row['phone']) ?></td>
                    <td><span style="font-weight:700;"><?= number_format($row['qtd_vendas']) ?></span></td>
                    <td><span class="val-green">R$ <?= number_format($row['total_vendas'], 2, ',', '.') ?></span></td>
                    <td><span class="val-amber">R$ <?= number_format($row['amount_pending'], 2, ',', '.') ?></span></td>
                    <td><?= $row['percentage'] ?>%</td>
                    <td>
                        <?php if ($row['status'] == 1): ?>
                            <span class="badge-ativo">Ativo</span>
                        <?php else: ?>
                            <span class="badge-inativo">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button
                            class="btn-copy-link"
                            onclick="copyAffLink('<?= htmlspecialchars($linkAff, ENT_QUOTES) ?>', this)"
                            title="<?= htmlspecialchars($linkAff, ENT_QUOTES) ?>">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1z"/>
                                <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0z"/>
                            </svg>
                            Copiar link
                        </button>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;">
                            <a href="./?page=affiliates/create_affiliate&id=<?= $row['referral_id'] ?>">
                                <button class="btn-action btn-edit" title="Editar">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                                </button>
                            </a>
                            <a class="delete_affiliate" href="javascript:void(0)" @click="openModal" data-id="<?= $row['referral_id'] ?>">
                                <button class="btn-action btn-delete" title="Deletar">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                </button>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr>
                    <td colspan="9" style="text-align:center;padding:40px;color:#9ca3af;">
                        Nenhum afiliado encontrado.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- ===== Paginação ===== -->
        <?php if ($totalPages > 1): ?>
        <div class="aff-pagination">
            <?php
            $sorteioParam = $sorteioFiltro > 0 ? "&sorteio=$sorteioFiltro" : "";
            if ($page > 1):
            ?>
            <a class="pg-btn" href="./?page=affiliates&pg=<?= $page - 1 . $sorteioParam ?>">&#8592;</a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
                $active = $i == $page ? 'active' : '';
            ?>
            <a class="pg-btn <?= $active ?>" href="./?page=affiliates&pg=<?= $i . $sorteioParam ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
            <a class="pg-btn" href="./?page=affiliates&pg=<?= $page + 1 . $sorteioParam ?>">&#8594;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

</div><!-- .aff-dash -->

<!-- ===== Modal Deletar ===== -->
<div x-show="isModalOpen"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-30 flex items-end bg-black bg-opacity-50 sm:items-center sm:justify-center"
     style="display:none;">
    <div x-show="isModalOpen"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 transform translate-y-1/2"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0 transform translate-y-1/2"
         @click.away="closeModal"
         @keydown.escape="closeModal"
         class="w-full px-6 py-4 overflow-hidden bg-white rounded-t-lg dark:bg-gray-800 sm:rounded-lg sm:m-4 sm:max-w-xl"
         role="dialog" id="modal" style="display:none;">
        <header class="flex justify-end">
            <button class="inline-flex items-center justify-center w-6 h-6 text-gray-400 transition-colors duration-150 rounded dark:hover:text-gray-200 hover:text-gray-700" aria-label="close" @click="closeModal">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" fill-rule="evenodd"/></svg>
            </button>
        </header>
        <div class="mt-4 mb-6">
            <p class="mb-2 text-lg font-semibold text-gray-700 dark:text-gray-300">Deseja excluir?</p>
            <p class="text-sm text-gray-700 dark:text-gray-400">Você realmente deseja excluir esse afiliado?</p>
        </div>
        <footer class="flex flex-col items-center justify-end px-6 py-3 -mx-6 -mb-4 space-y-4 sm:space-y-0 sm:space-x-6 sm:flex-row bg-gray-50 dark:bg-gray-800">
            <button @click="closeModal" class="w-full px-5 py-3 text-sm font-medium leading-5 text-gray-700 transition-colors duration-150 border border-gray-300 rounded-lg dark:text-gray-400 sm:px-4 sm:py-2 sm:w-auto hover:border-gray-500 focus:outline-none">Não</button>
            <button class="delete_data w-full px-5 py-3 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-red-600 border border-transparent rounded-lg sm:w-auto sm:px-4 sm:py-2 hover:bg-red-700 focus:outline-none">Sim, excluir</button>
        </footer>
    </div>
</div>

<!-- Toast de confirmação -->
<div id="aff-toast">
    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
    Link copiado!
</div>

<script>
function copyAffLink(url, btn) {
    navigator.clipboard.writeText(url).then(function() {
        var toast = document.getElementById('aff-toast');
        toast.classList.add('show');
        setTimeout(function(){ toast.classList.remove('show'); }, 2200);
    }).catch(function() {
        // fallback
        var ta = document.createElement('textarea');
        ta.value = url;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        var toast = document.getElementById('aff-toast');
        toast.classList.add('show');
        setTimeout(function(){ toast.classList.remove('show'); }, 2200);
    });
}

$(document).ready(function(){
    $('.delete_affiliate').click(function(){
        var id = $(this).attr('data-id');
        $('.delete_data').attr('data-id', id);
    });
    $('.delete_data').click(function(){
        var id = $(this).attr('data-id');
        delete_affiliate(id);
    });
});

function delete_affiliate($id){
    $.ajax({
        url: _base_url_ + 'class/Main.php?action=delete_affiliate',
        method: 'POST',
        data: {id: $id},
        dataType: 'json',
        error: function(err){
            console.log(err);
            alert('[AP01] - An error occured.');
        },
        success: function(resp){
            if(typeof resp == 'object' && resp.status == 'success'){
                location.reload();
            } else {
                alert('[AP02] - An error occured.');
            }
        }
    });
}
</script>