<?php
// ------------------------------------------------------------------
// Filtros recebidos via GET
// ------------------------------------------------------------------
$filtroAfiliado = isset($_GET['aff'])        ? intval($_GET['aff'])           : 0;
$filtroSorteio  = isset($_GET['sorteio'])    ? intval($_GET['sorteio'])        : 0;
$filtroStatus   = isset($_GET['status'])     ? intval($_GET['status'])         : -1; // -1 = todos
$filtroDateIni  = isset($_GET['date_ini'])   ? $conn->real_escape_string($_GET['date_ini']) : '';
$filtroDateFim  = isset($_GET['date_fim'])   ? $conn->real_escape_string($_GET['date_fim']) : '';

// ------------------------------------------------------------------
// Listas para os dropdowns
// ------------------------------------------------------------------
$qAfiliados = $conn->query("
    SELECT r.id, r.referral_code, c.firstname, c.lastname, c.phone
    FROM referral r
    INNER JOIN customer_list c ON c.id = r.customer_id
    ORDER BY c.firstname ASC
");

$qProdutos = $conn->query("SELECT id, name FROM product_list ORDER BY id DESC");

// ------------------------------------------------------------------
// Montagem do WHERE dinâmico
// ------------------------------------------------------------------
$whereParts = ["o.referral_id IS NOT NULL"];

if ($filtroAfiliado > 0) {
    // busca o referral_code do afiliado selecionado
    $rc = $conn->query("SELECT referral_code FROM referral WHERE id = $filtroAfiliado")->fetch_assoc();
    if ($rc) {
        $whereParts[] = "o.referral_id = '" . $conn->real_escape_string($rc['referral_code']) . "'";
    }
}

if ($filtroSorteio > 0) {
    $whereParts[] = "o.product_id = $filtroSorteio";
}

if ($filtroStatus >= 0) {
    $whereParts[] = "o.status = $filtroStatus";
}

if ($filtroDateIni !== '') {
    $whereParts[] = "o.date_created >= '$filtroDateIni 00:00:00'";
}
if ($filtroDateFim !== '') {
    $whereParts[] = "o.date_created <= '$filtroDateFim 23:59:59'";
}

$whereSQL = count($whereParts) ? 'WHERE ' . implode(' AND ', $whereParts) : '';

// ------------------------------------------------------------------
// Totalizadores do cabeçalho (sempre refletem os filtros)
// ------------------------------------------------------------------
$qTotais = $conn->query("
    SELECT
        COUNT(o.id)                                                AS total_vendas,
        COALESCE(SUM(CASE WHEN o.status = 2 THEN o.total_amount ELSE 0 END), 0) AS total_pago,
        COALESCE(SUM(CASE WHEN o.status = 1 THEN o.total_amount ELSE 0 END), 0) AS total_pendente,
        COUNT(DISTINCT o.referral_id)                             AS total_afiliados
    FROM order_list o
    $whereSQL
")->fetch_assoc();

$totalVendas    = $qTotais['total_vendas']    ?? 0;
$totalPago      = $qTotais['total_pago']      ?? 0;
$totalPendente  = $qTotais['total_pendente']  ?? 0;
$totalAfiliados = $qTotais['total_afiliados'] ?? 0;

// ------------------------------------------------------------------
// Paginação
// ------------------------------------------------------------------
$perPage = 25;
$page    = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$offset  = $perPage * ($page - 1);

$countResult  = $conn->query("SELECT COUNT(o.id) as t FROM order_list o $whereSQL")->fetch_assoc();
$totalResults = $countResult['t'] ?? 0;
$totalPages   = max(1, ceil($totalResults / $perPage));

// ------------------------------------------------------------------
// Query principal
// ------------------------------------------------------------------
$sql = "
    SELECT
        o.id            AS order_id,
        o.code,
        o.status,
        o.total_amount,
        o.quantity,
        o.product_name,
        o.product_id,
        o.payment_method,
        o.date_created,
        o.referral_id   AS referral_code,
        CONCAT(c.firstname, ' ', c.lastname) AS customer_name,
        c.phone         AS customer_phone,
        CONCAT(af.firstname, ' ', af.lastname) AS affiliate_name,
        af.phone        AS affiliate_phone,
        r.percentage,
        r.id            AS referral_id
    FROM order_list o
    INNER JOIN customer_list c  ON c.id  = o.customer_id
    LEFT  JOIN referral r        ON r.referral_code = o.referral_id
    LEFT  JOIN customer_list af  ON af.id = r.customer_id
    $whereSQL
    ORDER BY o.date_created DESC
    LIMIT $perPage OFFSET $offset
";
$result = $conn->query($sql);

// Monta string de params para links de paginação
$queryParams = [];
if ($filtroAfiliado > 0) $queryParams[] = "aff=$filtroAfiliado";
if ($filtroSorteio  > 0) $queryParams[] = "sorteio=$filtroSorteio";
if ($filtroStatus  >= 0) $queryParams[] = "status=$filtroStatus";
if ($filtroDateIni !== '') $queryParams[] = "date_ini=$filtroDateIni";
if ($filtroDateFim !== '') $queryParams[] = "date_fim=$filtroDateFim";
$paramStr = count($queryParams) ? '&' . implode('&', $queryParams) : '';
?>

<style>
/* ===== Base ===== */
.afs-wrap * { box-sizing: border-box; }
.afs-wrap {
    font-family: 'Inter', 'Poppins', sans-serif;
    padding-bottom: 48px;
}

/* ===== Header ===== */
.afs-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 26px;
}
.afs-header h2 {
    margin: 0;
    font-size: 1.55rem;
    font-weight: 700;
    color: #1a1a2e;
}
.dark .afs-header h2 { color: #e2e8f0; }

/* ===== Cards de métricas ===== */
.afs-stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
    gap: 14px;
    margin-bottom: 26px;
}
.afs-stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 18px 18px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    display: flex;
    align-items: center;
    gap: 14px;
}
.dark .afs-stat-card { background: #1e293b; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
.afs-stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.2rem;
}
.ic-purple { background: #ede9fe; color: #7c3aed; }
.ic-green  { background: #d1fae5; color: #065f46; }
.ic-amber  { background: #fef3c7; color: #92400e; }
.ic-blue   { background: #dbeafe; color: #1e40af; }
.dark .ic-purple { background: #4c1d95; color: #a78bfa; }
.dark .ic-green  { background: #064e3b; color: #6ee7b7; }
.dark .ic-amber  { background: #78350f; color: #fde68a; }
.dark .ic-blue   { background: #1e3a8a; color: #93c5fd; }
.afs-stat-info label {
    display: block;
    font-size: .68rem;
    font-weight: 600;
    color: #6b7280;
    letter-spacing: .04em;
    text-transform: uppercase;
    margin-bottom: 3px;
}
.dark .afs-stat-info label { color: #94a3b8; }
.afs-stat-info strong {
    font-size: 1.15rem;
    font-weight: 700;
    color: #111827;
}
.dark .afs-stat-info strong { color: #f1f5f9; }

/* ===== Painel de filtros ===== */
.afs-filter-panel {
    background: #fff;
    border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    margin-bottom: 20px;
}
.dark .afs-filter-panel { background: #1e293b; }
.afs-filter-panel h4 {
    margin: 0 0 14px;
    font-size: .85rem;
    font-weight: 700;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 6px;
}
.dark .afs-filter-panel h4 { color: #cbd5e1; }
.afs-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 12px;
    align-items: end;
}
.afs-field label {
    display: block;
    font-size: .72rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.dark .afs-field label { color: #94a3b8; }
.afs-field select,
.afs-field input[type="date"] {
    width: 100%;
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    font-size: .84rem;
    background: #f9fafb;
    color: #111827;
    outline: none;
    transition: border .15s;
}
.afs-field select:focus,
.afs-field input[type="date"]:focus { border-color: #7c3aed; }
.dark .afs-field select,
.dark .afs-field input[type="date"] {
    background: #0f172a;
    border-color: #334155;
    color: #f1f5f9;
}
.afs-filter-actions { display: flex; gap: 8px; align-items: flex-end; }
.btn-f {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all .15s;
    white-space: nowrap;
}
.btn-f-purple { background: #7c3aed; color: #fff; }
.btn-f-purple:hover { background: #6d28d9; }
.btn-f-gray   { background: #f3f4f6; color: #374151; }
.btn-f-gray:hover { background: #e5e7eb; }
.dark .btn-f-gray { background: #334155; color: #cbd5e1; }

/* ===== Tabela ===== */
.afs-table-wrap {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    overflow: hidden;
}
.dark .afs-table-wrap { background: #1e293b; }
.afs-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .83rem;
}
.afs-table thead tr {
    background: #f8fafc;
    font-size: .69rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
}
.dark .afs-table thead tr {
    background: #0f172a;
    color: #94a3b8;
    border-bottom: 1px solid #334155;
}
.afs-table th,
.afs-table td {
    padding: 11px 15px;
    text-align: left;
    vertical-align: middle;
}
.afs-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background .1s;
}
.dark .afs-table tbody tr { border-bottom: 1px solid #1e2d3d; }
.afs-table tbody tr:hover { background: #f8fafc; }
.dark .afs-table tbody tr:hover { background: #0f172a; }
.afs-table td { color: #374151; }
.dark .afs-table td { color: #cbd5e1; }

/* Status badges */
.badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: .7rem;
    font-weight: 700;
    white-space: nowrap;
}
.badge-pago      { background: #d1fae5; color: #065f46; }
.badge-pendente  { background: #fef3c7; color: #92400e; }
.badge-cancelado { background: #fee2e2; color: #991b1b; }
.dark .badge-pago      { background: #064e3b; color: #6ee7b7; }
.dark .badge-pendente  { background: #78350f; color: #fde68a; }
.dark .badge-cancelado { background: #7f1d1d; color: #fca5a5; }

/* Valores */
.val-green { color: #059669; font-weight: 700; }
.val-amber { color: #d97706; font-weight: 700; }
.val-gray  { color: #9ca3af; }
.dark .val-green { color: #34d399; }
.dark .val-amber { color: #fbbf24; }

/* Avatares iniciais */
.mini-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .7rem;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
    margin-right: 7px;
}
.av-purple { background: linear-gradient(135deg, #7c3aed, #4f46e5); }
.av-teal   { background: linear-gradient(135deg, #0d9488, #0284c7); }
.cell-person { display: flex; align-items: center; }

/* Comissão calculada */
.comissao-pill {
    background: #ede9fe;
    color: #7c3aed;
    padding: 2px 9px;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
}
.dark .comissao-pill { background: #4c1d95; color: #c4b5fd; }

/* Sort links */
.afs-table thead a { color: inherit; text-decoration: none; }
.afs-table thead a:hover { color: #7c3aed; }

/* Botão ver pedido */
.btn-view {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: #ede9fe;
    color: #7c3aed;
    border: none;
    cursor: pointer;
    transition: all .15s;
    text-decoration: none;
}
.btn-view:hover { background: #7c3aed; color: #fff; }

/* Linha de totais */
.tfoot-totals td {
    background: #f8fafc;
    font-weight: 700;
    font-size: .82rem;
    color: #374151;
    border-top: 2px solid #e5e7eb;
}
.dark .tfoot-totals td { background: #0f172a; color: #e2e8f0; border-top: 2px solid #334155; }

/* Sem resultados */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}
.empty-state svg { margin-bottom: 12px; opacity: .4; }
.empty-state p { font-size: .9rem; margin: 0; }

/* ===== Paginação ===== */
.afs-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 20px;
    flex-wrap: wrap;
    gap: 8px;
}
.afs-pagination-info {
    font-size: .78rem;
    color: #6b7280;
}
.dark .afs-pagination-info { color: #94a3b8; }
.pg-btns { display: flex; gap: 4px; flex-wrap: wrap; }
.pg-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    border-radius: 7px;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #374151;
    font-size: .8rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all .15s;
    padding: 0 7px;
}
.pg-btn:hover, .pg-btn.active { background: #7c3aed; color: #fff; border-color: #7c3aed; }
.dark .pg-btn { background: #0f172a; border-color: #334155; color: #cbd5e1; }
.dark .pg-btn:hover, .dark .pg-btn.active { background: #7c3aed; border-color: #7c3aed; color: #fff; }

/* Responsivo */
@media (max-width: 900px) {
    .afs-table th:nth-child(4),
    .afs-table td:nth-child(4),
    .afs-table th:nth-child(7),
    .afs-table td:nth-child(7) { display: none; }
}
@media (max-width: 640px) {
    .afs-table th:nth-child(3),
    .afs-table td:nth-child(3),
    .afs-table th:nth-child(6),
    .afs-table td:nth-child(6) { display: none; }
}
</style>

<div class="afs-wrap">

    <!-- ===== Cabeçalho ===== -->
    <div class="afs-header">
        <h2>💼 Vendas dos Afiliados</h2>
        <a href="./?page=affiliates" class="btn-f btn-f-gray">
            ← Voltar ao painel
        </a>
    </div>

    <!-- ===== Cards de totais (baseados nos filtros) ===== -->
    <div class="afs-stat-grid">
        <div class="afs-stat-card">
            <div class="afs-stat-icon ic-purple">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
            </div>
            <div class="afs-stat-info">
                <label>Total de vendas</label>
                <strong><?= number_format($totalVendas) ?></strong>
            </div>
        </div>
        <div class="afs-stat-card">
            <div class="afs-stat-icon ic-green">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.028 2.353 1.118V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.028-2.354-1.118V5z" clip-rule="evenodd"/></svg>
            </div>
            <div class="afs-stat-info">
                <label>Total pago</label>
                <strong class="val-green">R$ <?= number_format($totalPago, 2, ',', '.') ?></strong>
            </div>
        </div>
        <div class="afs-stat-card">
            <div class="afs-stat-icon ic-amber">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/></svg>
            </div>
            <div class="afs-stat-info">
                <label>Total pendente</label>
                <strong class="val-amber">R$ <?= number_format($totalPendente, 2, ',', '.') ?></strong>
            </div>
        </div>
        <div class="afs-stat-card">
            <div class="afs-stat-icon ic-blue">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/></svg>
            </div>
            <div class="afs-stat-info">
                <label>Afiliados ativos</label>
                <strong><?= number_format($totalAfiliados) ?></strong>
            </div>
        </div>
    </div>

    <!-- ===== Filtros ===== -->
    <div class="afs-filter-panel">
        <h4>
            <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/></svg>
            Filtrar vendas
        </h4>
        <form method="GET" id="afs-filter-form">
            <input type="hidden" name="page" value="affiliates/sales">
            <div class="afs-filter-grid">

                <div class="afs-field">
                    <label>Afiliado</label>
                    <select name="aff">
                        <option value="0">Todos os afiliados</option>
                        <?php
                        $afsArr = [];
                        while ($a = $qAfiliados->fetch_assoc()) { $afsArr[] = $a; }
                        foreach ($afsArr as $a):
                            $sel = ($filtroAfiliado == $a['id']) ? 'selected' : '';
                        ?>
                        <option value="<?= $a['id'] ?>" <?= $sel ?>>
                            <?= htmlspecialchars($a['firstname'] . ' ' . $a['lastname']) ?>
                            (<?= formatPhoneNumber($a['phone']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="afs-field">
                    <label>Sorteio</label>
                    <select name="sorteio">
                        <option value="0">Todos os sorteios</option>
                        <?php
                        while ($p = $qProdutos->fetch_assoc()):
                            $sel = ($filtroSorteio == $p['id']) ? 'selected' : '';
                        ?>
                        <option value="<?= $p['id'] ?>" <?= $sel ?>><?= htmlspecialchars($p['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="afs-field">
                    <label>Status</label>
                    <select name="status">
                        <option value="-1" <?= $filtroStatus == -1 ? 'selected' : '' ?>>Todos</option>
                        <option value="1"  <?= $filtroStatus ==  1 ? 'selected' : '' ?>>Pendente</option>
                        <option value="2"  <?= $filtroStatus ==  2 ? 'selected' : '' ?>>Pago</option>
                        <option value="3"  <?= $filtroStatus ==  3 ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>

                <div class="afs-field">
                    <label>Data inicial</label>
                    <input type="date" name="date_ini" value="<?= htmlspecialchars($filtroDateIni) ?>">
                </div>

                <div class="afs-field">
                    <label>Data final</label>
                    <input type="date" name="date_fim" value="<?= htmlspecialchars($filtroDateFim) ?>">
                </div>

                <div class="afs-filter-actions">
                    <button type="submit" class="btn-f btn-f-purple">
                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                        Filtrar
                    </button>
                    <?php if ($paramStr): ?>
                    <a href="./?page=affiliates/sales" class="btn-f btn-f-gray">✕ Limpar</a>
                    <?php endif; ?>
                </div>

            </div>
        </form>
    </div>

    <!-- ===== Tabela de vendas ===== -->
    <div class="afs-table-wrap">
        <table class="afs-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Comprador</th>
                    <th>Afiliado</th>
                    <th>Sorteio</th>
                    <th>Qtd.</th>
                    <th>Valor</th>
                    <th>Comissão</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $subtotalValor = 0;
            $subtotalComis = 0;
            $count = 0;

            if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                    $count++;
                    $comissao = ($row['total_amount'] * $row['percentage']) / 100;
                    $subtotalValor += $row['total_amount'];
                    $subtotalComis += $comissao;

                    // Iniciais comprador
                    $buyerName  = $row['customer_name'] ?: '?';
                    $buyerInit  = strtoupper(substr($buyerName, 0, 1));
                    // Iniciais afiliado
                    $affName    = $row['affiliate_name'] ?: 'N/A';
                    $affInit    = strtoupper(substr($affName, 0, 1));

                    // Badge status
                    switch ($row['status']) {
                        case 1: $badgeClass = 'badge-pendente';  $badgeLabel = 'Pendente';   break;
                        case 2: $badgeClass = 'badge-pago';      $badgeLabel = 'Pago';        break;
                        case 3: $badgeClass = 'badge-cancelado'; $badgeLabel = 'Cancelado';   break;
                        default: $badgeClass = ''; $badgeLabel = $row['status'];
                    }
                    $valClass = $row['status'] == 2 ? 'val-green' : ($row['status'] == 3 ? 'val-gray' : 'val-amber');
            ?>
                <tr>
                    <td><span style="font-size:.75rem;color:#9ca3af;"><?= htmlspecialchars($row['code']) ?></span></td>
                    <td>
                        <div class="cell-person">
                            <span class="mini-avatar av-teal"><?= $buyerInit ?></span>
                            <span>
                                <?= htmlspecialchars($buyerName) ?><br>
                                <small style="color:#9ca3af;"><?= formatPhoneNumber($row['customer_phone']) ?></small>
                            </span>
                        </div>
                    </td>
                    <td>
                        <?php if ($row['affiliate_name']): ?>
                        <div class="cell-person">
                            <span class="mini-avatar av-purple"><?= $affInit ?></span>
                            <span>
                                <?= htmlspecialchars($affName) ?><br>
                                <small style="color:#9ca3af;"><?= formatPhoneNumber($row['affiliate_phone']) ?></small>
                            </span>
                        </div>
                        <?php else: ?>
                        <span class="val-gray">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                    <td style="text-align:center;"><?= number_format($row['quantity']) ?></td>
                    <td><span class="<?= $valClass ?>">R$ <?= number_format($row['total_amount'], 2, ',', '.') ?></span></td>
                    <td>
                        <?php if ($row['percentage']): ?>
                        <span class="comissao-pill">
                            R$ <?= number_format($comissao, 2, ',', '.') ?>
                            <small>(<?= $row['percentage'] ?>%)</small>
                        </span>
                        <?php else: ?>
                        <span class="val-gray">—</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
                    <td style="white-space:nowrap;">
                        <?= date('d/m/Y', strtotime($row['date_created'])) ?><br>
                        <small style="color:#9ca3af;"><?= date('H:i', strtotime($row['date_created'])) ?></small>
                    </td>
                    <td>
                        <a href="./?page=orders/view_order&id=<?= $row['order_id'] ?>" class="btn-view" title="Ver pedido">
                            <svg width="13" height="13" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                        </a>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr>
                    <td colspan="10">
                        <div class="empty-state">
                            <svg width="40" height="40" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 4a3 3 0 00-3 3v6a3 3 0 003 3h10a3 3 0 003-3V7a3 3 0 00-3-3H5zm-1 9v-1h5v2H5a1 1 0 01-1-1zm7 1h4a1 1 0 001-1v-1h-5v2zm0-4h5V8h-5v2zM9 8H4v2h5V8z" clip-rule="evenodd"/></svg>
                            <p>Nenhuma venda encontrada com os filtros selecionados.</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
            <?php if ($count > 0): ?>
            <tfoot>
                <tr class="tfoot-totals">
                    <td colspan="5" style="text-align:right;padding-right:8px;">Subtotal desta página:</td>
                    <td><span class="val-green">R$ <?= number_format($subtotalValor, 2, ',', '.') ?></span></td>
                    <td><span class="comissao-pill">R$ <?= number_format($subtotalComis, 2, ',', '.') ?></span></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>

        <!-- ===== Paginação ===== -->
        <div class="afs-pagination">
            <span class="afs-pagination-info">
                Exibindo <?= max(1, $offset+1) ?>–<?= min($offset+$perPage, $totalResults) ?> de <?= number_format($totalResults) ?> vendas
            </span>
            <?php if ($totalPages > 1): ?>
            <div class="pg-btns">
                <?php if ($page > 1): ?>
                <a class="pg-btn" href="./?page=affiliates/sales&pg=<?= $page-1 . $paramStr ?>">&#8592;</a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end   = min($totalPages, $page + 2);
                for ($i = $start; $i <= $end; $i++):
                    $act = ($i == $page) ? 'active' : '';
                ?>
                <a class="pg-btn <?= $act ?>" href="./?page=affiliates/sales&pg=<?= $i . $paramStr ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                <a class="pg-btn" href="./?page=affiliates/sales&pg=<?= $page+1 . $paramStr ?>">&#8594;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- .afs-wrap -->
