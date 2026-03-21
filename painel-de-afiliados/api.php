<?php
/**
 * API do Painel de Afiliados
 * Endpoint AJAX — retorna JSON
 */

require_once(__DIR__ . '/config.php');
header('Content-Type: application/json; charset=utf-8');

$action = isset($_GET['action']) ? strtolower(trim($_GET['action'])) : '';

switch ($action) {

    // ─── LOGIN POR TELEFONE ───────────────────────────────────────
    case 'login':
        $phone = isset($_POST['phone']) ? preg_replace('/[^0-9]/', '', $_POST['phone']) : '';

        if (strlen($phone) < 10) {
            echo json_encode(['status' => 'failed', 'msg' => 'Telefone inválido.']);
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM customer_list WHERE phone = ? LIMIT 1");
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['status' => 'failed', 'msg' => 'Telefone não encontrado. Faça seu cadastro.']);
            exit;
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        // Se não é afiliado, torna automaticamente
        if ($user['is_affiliate'] != 1) {
            $code = generate_referral_code($conn);
            $pct = get_default_percentage($conn);
            $ins = $conn->prepare("INSERT INTO referral (customer_id, referral_code, percentage, amount_pending, amount_paid, status) VALUES (?, ?, ?, 0, 0, 1)");
            $ins->bind_param('isi', $user['id'], $code, $pct);
            $ins->execute();
            $ins->close();
            $conn->query("UPDATE customer_list SET is_affiliate = 1 WHERE id = " . intval($user['id']));
            $user['is_affiliate'] = 1;
        }

        aff_login($user);
        echo json_encode(['status' => 'success', 'msg' => 'Login realizado!']);
        break;

    // ─── CADASTRO + AFILIADO ──────────────────────────────────────
    case 'register':
        $firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
        $lastname  = isset($_POST['lastname'])  ? trim($_POST['lastname'])  : '';
        $phone     = isset($_POST['phone'])     ? preg_replace('/[^0-9]/', '', $_POST['phone']) : '';

        if (!$firstname || !$lastname || !$phone) {
            echo json_encode(['status' => 'failed', 'msg' => 'Preencha todos os campos.']);
            exit;
        }
        if (strlen($phone) < 10) {
            echo json_encode(['status' => 'failed', 'msg' => 'Telefone inválido.']);
            exit;
        }

        // Verifica se telefone já existe
        $chk = $conn->prepare("SELECT id, is_affiliate FROM customer_list WHERE phone = ? LIMIT 1");
        $chk->bind_param('s', $phone);
        $chk->execute();
        $res = $chk->get_result();

        if ($res->num_rows > 0) {
            // Já existe — faz login e vira afiliado se preciso
            $existing = $res->fetch_assoc();
            $chk->close();

            if ($existing['is_affiliate'] != 1) {
                $code = generate_referral_code($conn);
                $pct = get_default_percentage($conn);
                $ins = $conn->prepare("INSERT INTO referral (customer_id, referral_code, percentage, amount_pending, amount_paid, status) VALUES (?, ?, ?, 0, 0, 1)");
                $ins->bind_param('isi', $existing['id'], $code, $pct);
                $ins->execute();
                $ins->close();
                $conn->query("UPDATE customer_list SET is_affiliate = 1 WHERE id = " . intval($existing['id']));
            }

            $user = $conn->query("SELECT * FROM customer_list WHERE id = " . intval($existing['id']))->fetch_assoc();
            aff_login($user);
            echo json_encode(['status' => 'success', 'msg' => 'Telefone já cadastrado. Login realizado!']);
            exit;
        }
        $chk->close();

        // Cria conta nova
        $date_added = date('Y-m-d H:i:s');
        $ins = $conn->prepare("INSERT INTO customer_list (firstname, lastname, phone, is_affiliate, date_added) VALUES (?, ?, ?, 1, ?)");
        $ins->bind_param('ssss', $firstname, $lastname, $phone, $date_added);

        if (!$ins->execute()) {
            echo json_encode(['status' => 'failed', 'msg' => 'Erro ao criar conta.']);
            exit;
        }

        $new_id = $conn->insert_id;
        $ins->close();

        // Cria referral
        $code = generate_referral_code($conn);
        $pct = get_default_percentage($conn);
        $ref = $conn->prepare("INSERT INTO referral (customer_id, referral_code, percentage, amount_pending, amount_paid, status) VALUES (?, ?, ?, 0, 0, 1)");
        $ref->bind_param('isi', $new_id, $code, $pct);
        $ref->execute();
        $ref->close();

        // Login automático
        $user = $conn->query("SELECT * FROM customer_list WHERE id = " . intval($new_id))->fetch_assoc();
        aff_login($user);
        echo json_encode(['status' => 'success', 'msg' => 'Conta criada com sucesso!']);
        break;

    // ─── DADOS DO DASHBOARD ───────────────────────────────────────
    case 'dashboard':
        if (!aff_logged_in()) {
            echo json_encode(['status' => 'failed', 'msg' => 'Não autenticado.']);
            exit;
        }

        $uid = intval(aff_user('id'));

        // Dados do referral
        $ref = $conn->query("SELECT * FROM referral WHERE customer_id = $uid AND status = 1 LIMIT 1");
        if (!$ref || $ref->num_rows === 0) {
            echo json_encode(['status' => 'failed', 'msg' => 'Dados de afiliado não encontrados.']);
            exit;
        }
        $r = $ref->fetch_assoc();
        $code = $r['referral_code'];

        // Contagem e soma de vendas
        $sales = $conn->query("
            SELECT 
                COUNT(o.id) as total_vendas,
                COALESCE(SUM(o.order_total), 0) as total_valor,
                COUNT(CASE WHEN o.status = 2 THEN 1 END) as vendas_pagas,
                COUNT(CASE WHEN o.status = 1 THEN 1 END) as vendas_pendentes
            FROM order_list o 
            WHERE o.referral_id = '" . $conn->real_escape_string($code) . "'
        ");
        $s = $sales ? $sales->fetch_assoc() : ['total_vendas' => 0, 'total_valor' => 0, 'vendas_pagas' => 0, 'vendas_pendentes' => 0];

        // Lista de vendas recentes (últimas 50)
        $list = $conn->query("
            SELECT o.id, o.order_total, o.status, o.date_added,
                   c.firstname, c.lastname,
                   p.name as product_name
            FROM order_list o
            LEFT JOIN customer_list c ON c.id = o.customer_id
            LEFT JOIN product_list p ON p.id = o.product_id
            WHERE o.referral_id = '" . $conn->real_escape_string($code) . "'
            ORDER BY o.date_added DESC
            LIMIT 50
        ");

        $vendas = [];
        if ($list) {
            while ($row = $list->fetch_assoc()) {
                $vendas[] = [
                    'id'       => $row['id'],
                    'cliente'  => $row['firstname'] . ' ' . $row['lastname'],
                    'produto'  => $row['product_name'],
                    'valor'    => number_format($row['order_total'], 2, ',', '.'),
                    'status'   => intval($row['status']),
                    'data'     => date('d/m/Y H:i', strtotime($row['date_added'])),
                ];
            }
        }

        $link = BASE_REF . '?&ref=' . $code;

        echo json_encode([
            'status'          => 'success',
            'nome'            => aff_user('firstname') . ' ' . aff_user('lastname'),
            'referral_code'   => $code,
            'link'            => $link,
            'percentage'      => $r['percentage'],
            'amount_pending'  => number_format($r['amount_pending'], 2, ',', '.'),
            'amount_paid'     => number_format($r['amount_paid'], 2, ',', '.'),
            'total_vendas'    => intval($s['total_vendas']),
            'total_valor'     => number_format($s['total_valor'], 2, ',', '.'),
            'vendas_pagas'    => intval($s['vendas_pagas']),
            'vendas_pendentes'=> intval($s['vendas_pendentes']),
            'vendas'          => $vendas,
        ]);
        break;

    // ─── LOGOUT ───────────────────────────────────────────────────
    case 'logout':
        aff_logout();
        echo json_encode(['status' => 'success']);
        break;

    default:
        echo json_encode(['status' => 'failed', 'msg' => 'Ação inválida.']);
        break;
}

// ─── HELPERS ──────────────────────────────────────────────────────
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
