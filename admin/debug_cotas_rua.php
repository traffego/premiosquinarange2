<?php
/**
 * Visualizador do log de debug das cotas de rua
 * Acesse via: /admin/debug_cotas_rua.php
 */

require_once '../settings.php';

// Autenticação básica via sessão (mesma lógica do admin)
if (session_status() == PHP_SESSION_NONE) session_start();
if (empty($_SESSION['userdata'])) {
    header('Location: ' . BASE_URL . 'admin/login.php');
    exit();
}

$log_file = $_SERVER['DOCUMENT_ROOT'] . '/debug_cotas_rua.log';
$max_lines = isset($_GET['lines']) ? max(10, min(500, (int)$_GET['lines'])) : 100;
$auto_refresh = isset($_GET['refresh']) ? (int)$_GET['refresh'] : 0;

// Limpar log
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    file_put_contents($log_file, '');
    header('Location: debug_cotas_rua.php?cleared=1&lines=' . $max_lines . '&refresh=' . $auto_refresh);
    exit();
}

// Ler últimas N linhas
$lines = [];
$file_size = 0;
if (file_exists($log_file)) {
    $file_size = filesize($log_file);
    $all_lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_slice($all_lines, -$max_lines);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Debug Cotas de Rua</title>
<?php if ($auto_refresh > 0): ?>
<meta http-equiv="refresh" content="<?= $auto_refresh ?>">
<?php endif; ?>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Consolas', 'Courier New', monospace; background: #0f0f0f; color: #cfcfcf; padding: 20px; }
  h1 { font-family: Arial, sans-serif; color: #e0e0e0; font-size: 18px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
  .meta { font-family: Arial, sans-serif; font-size: 12px; color: #666; margin-bottom: 12px; }
  .meta span { margin-right: 16px; }
  .bar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 14px; font-family: Arial, sans-serif; }
  .bar label { color: #888; font-size: 12px; }
  .btn {
    background: #272727; color: #bbb; border: 1px solid #444;
    padding: 5px 12px; border-radius: 4px; text-decoration: none;
    cursor: pointer; font-size: 12px; display: inline-block;
  }
  .btn:hover { background: #383838; }
  .btn.on { background: #1a4a7a; border-color: #1e6ec0; color: #7fc8ff; }
  .btn.danger { background: #3a1010; border-color: #7a2020; color: #ff8888; }
  .btn.danger:hover { background: #5a1a1a; }
  .sep { border-left: 1px solid #333; height: 22px; }
  .logbox {
    background: #080808; border: 1px solid #2a2a2a; border-radius: 6px;
    padding: 12px 14px; max-height: calc(100vh - 140px); overflow-y: auto;
    overflow-x: auto;
  }
  .line { font-size: 12.5px; line-height: 1.7; padding: 1px 0; border-bottom: 1px solid #111; white-space: pre-wrap; word-break: break-all; }
  .line:last-child { border: none; }
  .ts  { color: #555; }
  .kv  { color: #569cd6; }
  .rng { color: #ce9178; }
  .yes { color: #f44747; font-weight: bold; }
  .no  { color: #4ec9b0; }
  .empty { color: #444; text-align: center; padding: 50px; font-family: Arial, sans-serif; font-size: 14px; }
  .cleared { color: #4ec9b0; margin-left: 8px; }
</style>
</head>
<body>
<h1>🐛 Debug — Cotas de Rua
  <?php if (isset($_GET['cleared'])): ?><small class="cleared">✅ Log limpo</small><?php endif; ?>
</h1>

<div class="meta">
  <span>📄 <code><?= htmlspecialchars($log_file) ?></code></span>
  <span>💾 <?= $file_size > 0 ? number_format($file_size / 1024, 1) . ' KB' : '0 KB' ?></span>
  <span>📋 <?= count($lines) ?> linhas exibidas</span>
</div>

<div class="bar">
  <label>Linhas:</label>
  <?php foreach ([50, 100, 200, 500] as $n): ?>
    <a href="?lines=<?= $n ?>&refresh=<?= $auto_refresh ?>" class="btn <?= $max_lines===$n?'on':'' ?>"><?= $n ?></a>
  <?php endforeach; ?>
  <div class="sep"></div>
  <label>Auto-refresh:</label>
  <?php foreach ([0=>'Off', 5=>'5s', 10=>'10s', 30=>'30s'] as $s=>$l): ?>
    <a href="?lines=<?= $max_lines ?>&refresh=<?= $s ?>" class="btn <?= $auto_refresh===$s?'on':'' ?>"><?= $l ?></a>
  <?php endforeach; ?>
  <div class="sep"></div>
  <a href="?lines=<?= $max_lines ?>&refresh=<?= $auto_refresh ?>" class="btn">🔄 Recarregar</a>
  <a href="?clear=1&lines=<?= $max_lines ?>&refresh=<?= $auto_refresh ?>"
     class="btn danger"
     onclick="return confirm('Limpar o log por completo?')">🗑 Limpar log</a>
</div>

<div class="logbox" id="lb">
<?php if (empty($lines)): ?>
  <div class="empty">Nenhuma entrada ainda.<br>Faça uma compra automática para gerar entradas.</div>
<?php else: ?>
  <?php foreach ($lines as $line): ?>
    <?php
      $h = htmlspecialchars($line);
      // timestamp
      $h = preg_replace('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', '<span class="ts">$1</span>', $h);
      // kv campos numéricos
      $h = preg_replace('/(product=\d+|qty_numbers[^=]*=\d+|globos=\d+|total_gen=\d+|sold_set_count=\d+|num\w+_key=[^\s]+)/', '<span class="kv">$1</span>', $h);
      // ranges JSON
      $h = preg_replace('/(ranges=\S+)/', '<span class="rng">$1</span>', $h);
      // bloqueado SIM
      $h = preg_replace('/(num\w+=SIM)/', '<span class="yes">$1</span>', $h);
      // bloqueado NAO
      $h = preg_replace('/(num\w+=NAO)/', '<span class="no">$1</span>', $h);
    ?>
    <div class="line"><?= $h ?></div>
  <?php endforeach; ?>
<?php endif; ?>
</div>
<script>
  // Scroll para o fim
  var lb = document.getElementById('lb');
  if (lb) lb.scrollTop = lb.scrollHeight;
</script>
</body>
</html>
