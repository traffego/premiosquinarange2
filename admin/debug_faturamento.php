<?php
/**
 * Script de diagnóstico para investigar discrepância de faturamento
 * Acesse via: /admin/index.php?page=debug_faturamento
 */

echo "<h1>Diagnóstico de Faturamento</h1>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;} table{border-collapse:collapse;margin:15px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f5f5f5;} .highlight{background:#fff3cd;} .error{background:#f8d7da;}</style>";

// 1. Total TODOS (sem filtro de product_id)
$queryTodos = $conn->query("SELECT SUM(total_amount) as total FROM order_list WHERE status = 2");
$rowTodos = $queryTodos->fetch_assoc();
$totalTodos = $rowTodos['total'] ?? 0;

echo "<h2>1. Faturamento Total (TODOS)</h2>";
echo "<p><strong>Total de pedidos pagos (status=2):</strong> R$ " . number_format($totalTodos, 2, ',', '.') . "</p>";

// 2. Listar todos os sorteios e seus totais
echo "<h2>2. Faturamento por Sorteio</h2>";
$querySorteios = $conn->query("SELECT id, name, status FROM product_list ORDER BY id DESC");

echo "<table>";
echo "<tr><th>ID</th><th>Nome</th><th>Status</th><th>Faturamento</th></tr>";

$somaSorteios = 0;
while ($sorteio = $querySorteios->fetch_assoc()) {
    $queryFat = $conn->query("SELECT SUM(total_amount) as total FROM order_list WHERE status = 2 AND product_id = " . intval($sorteio['id']));
    $rowFat = $queryFat->fetch_assoc();
    $totalSorteio = $rowFat['total'] ?? 0;
    $somaSorteios += $totalSorteio;
    
    $statusText = $sorteio['status'] == 1 ? 'Ativo' : 'Inativo';
    echo "<tr>";
    echo "<td>" . $sorteio['id'] . "</td>";
    echo "<td>" . htmlspecialchars($sorteio['name']) . "</td>";
    echo "<td>" . $statusText . "</td>";
    echo "<td>R$ " . number_format($totalSorteio, 2, ',', '.') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><strong>Soma dos sorteios individuais:</strong> R$ " . number_format($somaSorteios, 2, ',', '.') . "</p>";

// 3. Calcular diferença
$diferenca = $totalTodos - $somaSorteios;

echo "<h2>3. Análise da Discrepância</h2>";
echo "<table>";
echo "<tr><th>Descrição</th><th>Valor</th></tr>";
echo "<tr><td>Total TODOS</td><td>R$ " . number_format($totalTodos, 2, ',', '.') . "</td></tr>";
echo "<tr><td>Soma dos sorteios individuais</td><td>R$ " . number_format($somaSorteios, 2, ',', '.') . "</td></tr>";
echo "<tr class='" . (abs($diferenca) > 0.01 ? 'error' : '') . "'><td><strong>Diferença</strong></td><td><strong>R$ " . number_format($diferenca, 2, ',', '.') . "</strong></td></tr>";
echo "</table>";

// 4. Pedidos sem product_id válido
echo "<h2>4. Pedidos Problemáticos</h2>";

// 4.1 Pedidos com product_id NULL
$queryNull = $conn->query("SELECT id, code, total_amount, product_name, product_id, date_created FROM order_list WHERE status = 2 AND product_id IS NULL");
$countNull = $queryNull->num_rows;
$totalNull = 0;

echo "<h3>4.1 Pedidos pagos SEM product_id (NULL):</h3>";
if ($countNull > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Código</th><th>Nome Produto</th><th>Valor</th><th>Data</th></tr>";
    while ($row = $queryNull->fetch_assoc()) {
        $totalNull += $row['total_amount'];
        echo "<tr class='highlight'>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['code'] . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name'] ?? 'N/A') . "</td>";
        echo "<td>R$ " . number_format($row['total_amount'], 2, ',', '.') . "</td>";
        echo "<td>" . $row['date_created'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p>Total de pedidos sem product_id: <strong>" . $countNull . "</strong> | Valor: <strong>R$ " . number_format($totalNull, 2, ',', '.') . "</strong></p>";
} else {
    echo "<p>✅ Nenhum pedido pago sem product_id.</p>";
}

// 4.2 Pedidos com product_id que não existe na tabela product_list
$queryOrfao = $conn->query("
    SELECT o.id, o.code, o.total_amount, o.product_name, o.product_id, o.date_created 
    FROM order_list o 
    LEFT JOIN product_list p ON o.product_id = p.id 
    WHERE o.status = 2 AND o.product_id IS NOT NULL AND p.id IS NULL
");
$countOrfao = $queryOrfao->num_rows;
$totalOrfao = 0;

echo "<h3>4.2 Pedidos pagos com product_id INEXISTENTE (órfãos):</h3>";
if ($countOrfao > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Código</th><th>Product ID</th><th>Nome Produto</th><th>Valor</th><th>Data</th></tr>";
    while ($row = $queryOrfao->fetch_assoc()) {
        $totalOrfao += $row['total_amount'];
        echo "<tr class='error'>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['code'] . "</td>";
        echo "<td>" . $row['product_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name'] ?? 'N/A') . "</td>";
        echo "<td>R$ " . number_format($row['total_amount'], 2, ',', '.') . "</td>";
        echo "<td>" . $row['date_created'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p>Total de pedidos órfãos: <strong>" . $countOrfao . "</strong> | Valor: <strong>R$ " . number_format($totalOrfao, 2, ',', '.') . "</strong></p>";
} else {
    echo "<p>✅ Nenhum pedido pago com product_id inexistente.</p>";
}

// 5. Resumo final
echo "<h2>5. Resumo</h2>";
$totalProblematico = $totalNull + $totalOrfao;
echo "<table>";
echo "<tr><th>Categoria</th><th>Quantidade</th><th>Valor</th></tr>";
echo "<tr><td>Pedidos sem product_id</td><td>" . $countNull . "</td><td>R$ " . number_format($totalNull, 2, ',', '.') . "</td></tr>";
echo "<tr><td>Pedidos com product_id órfão</td><td>" . $countOrfao . "</td><td>R$ " . number_format($totalOrfao, 2, ',', '.') . "</td></tr>";
echo "<tr><td><strong>Total de problemas</strong></td><td><strong>" . ($countNull + $countOrfao) . "</strong></td><td><strong>R$ " . number_format($totalProblematico, 2, ',', '.') . "</strong></td></tr>";
echo "<tr><td><strong>Diferença calculada</strong></td><td colspan='2'><strong>R$ " . number_format($diferenca, 2, ',', '.') . "</strong></td></tr>";
echo "</table>";

// Verificar se a diferença bate
if (abs($diferenca - $totalProblematico) < 0.01) {
    echo "<p style='color:green;font-size:1.2em;'>✅ <strong>Diferença explicada!</strong> Os pedidos problemáticos representam exatamente a diferença entre TODOS e a soma individual.</p>";
} else {
    echo "<p style='color:red;font-size:1.2em;'>⚠️ <strong>Diferença NÃO explicada completamente.</strong> Pode haver outros fatores (arredondamentos, etc).</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?>
