<?php

require_once('../settings.php');

class Affiliate extends DBConnection
{
    private $settings = null;

    public function __construct()
    {
        global $_settings;
        $this->settings = $_settings;
        parent::__construct();
        ini_set('display_errors', 0);
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    // ─────────────────────────────────────────────────────────────────
    // Gera um referral_code único de 8 chars
    // ─────────────────────────────────────────────────────────────────
    private function generate_referral_code(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $check = $this->conn->query("SELECT id FROM referral WHERE referral_code = '$code' LIMIT 1");
        } while ($check && $check->num_rows > 0);

        return $code;
    }

    // ─────────────────────────────────────────────────────────────────
    // Percentual padrão de comissão
    // ─────────────────────────────────────────────────────────────────
    private function default_percentage(): int
    {
        $pct = (int) $this->settings->info('default_affiliate_percentage');
        return $pct > 0 ? $pct : 10;
    }

    // ─────────────────────────────────────────────────────────────────
    // ACTION: become_affiliate
    // Usuário já está logado e quer se tornar afiliado
    // ─────────────────────────────────────────────────────────────────
    public function become_affiliate(): string
    {
        $customer_id = $this->settings->userdata('id');

        if (!$customer_id) {
            return json_encode(['status' => 'failed', 'msg' => 'Você precisa estar logado.']);
        }

        // Verifica se já é afiliado
        $chk = $this->conn->prepare("SELECT id FROM referral WHERE customer_id = ? LIMIT 1");
        $chk->bind_param('i', $customer_id);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            // Garante que is_affiliate está marcado na sessão
            $this->settings->set_userdata('is_affiliate', 1);
            return json_encode(['status' => 'success', 'msg' => 'Você já é afiliado!']);
        }
        $chk->close();

        $code       = $this->generate_referral_code();
        $percentage = $this->default_percentage();

        $stmt = $this->conn->prepare(
            "INSERT INTO referral (customer_id, referral_code, percentage, amount_pending, amount_paid, status)
             VALUES (?, ?, ?, 0, 0, 1)"
        );
        $stmt->bind_param('isi', $customer_id, $code, $percentage);

        if (!$stmt->execute()) {
            return json_encode(['status' => 'failed', 'msg' => 'Erro ao cadastrar afiliado. Tente novamente.']);
        }
        $stmt->close();

        // Marca como afiliado em customer_list
        $upd = $this->conn->prepare("UPDATE customer_list SET is_affiliate = 1 WHERE id = ?");
        $upd->bind_param('i', $customer_id);
        $upd->execute();
        $upd->close();

        // Atualiza sessão
        $this->settings->set_userdata('is_affiliate', 1);

        return json_encode(['status' => 'success', 'msg' => 'Parabéns! Você agora é um afiliado.']);
    }

    // ─────────────────────────────────────────────────────────────────
    // ACTION: register_and_become_affiliate
    // Usuário deslogado: cria conta + vira afiliado + faz login
    // Recebe POST: firstname, lastname, phone
    // ─────────────────────────────────────────────────────────────────
    public function register_and_become_affiliate(): string
    {
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname  = trim($_POST['lastname']  ?? '');
        $phone     = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');

        if (!$firstname || !$lastname || !$phone) {
            return json_encode(['status' => 'failed', 'msg' => 'Preencha todos os campos.']);
        }

        if (strlen($phone) < 10) {
            return json_encode(['status' => 'failed', 'msg' => 'Telefone inválido.']);
        }

        // Verifica se o telefone já existe
        $chk = $this->conn->prepare("SELECT id, is_affiliate FROM customer_list WHERE phone = ? LIMIT 1");
        $chk->bind_param('s', $phone);
        $chk->execute();
        $res = $chk->get_result();
        $chk->close();

        if ($res->num_rows > 0) {
            $existing = $res->fetch_assoc();
            $customer_id = $existing['id'];

            // Faz login automático
            $row_full = $this->conn->query("SELECT * FROM customer_list WHERE id = '$customer_id' LIMIT 1")->fetch_assoc();
            foreach ($row_full as $k => $v) {
                $this->settings->set_userdata($k, $v);
            }
            $this->settings->set_userdata('login_type', 2);

            // Se já é afiliado, retorna sucesso direto
            if ($existing['is_affiliate'] == 1) {
                return json_encode(['status' => 'success', 'msg' => 'Você já é afiliado!']);
            }

            // Vira afiliado
            return $this->become_affiliate();
        }

        // Cria nova conta
        $date_added = date('Y-m-d H:i:s');
        $ins = $this->conn->prepare(
            "INSERT INTO customer_list (firstname, lastname, phone, is_affiliate, date_added)
             VALUES (?, ?, ?, 0, ?)"
        );
        $ins->bind_param('ssss', $firstname, $lastname, $phone, $date_added);

        if (!$ins->execute()) {
            return json_encode(['status' => 'failed', 'msg' => 'Erro ao criar conta. Tente novamente.']);
        }

        $customer_id = $this->conn->insert_id;
        $ins->close();

        // Faz login automático com os dados recém criados
        $row_full = $this->conn->query("SELECT * FROM customer_list WHERE id = '$customer_id' LIMIT 1")->fetch_assoc();
        foreach ($row_full as $k => $v) {
            $this->settings->set_userdata($k, $v);
        }
        $this->settings->set_userdata('login_type', 2);

        // Cria o registro de afiliado
        $code       = $this->generate_referral_code();
        $percentage = $this->default_percentage();

        $stmt = $this->conn->prepare(
            "INSERT INTO referral (customer_id, referral_code, percentage, amount_pending, amount_paid, status)
             VALUES (?, ?, ?, 0, 0, 1)"
        );
        $stmt->bind_param('isi', $customer_id, $code, $percentage);

        if (!$stmt->execute()) {
            return json_encode(['status' => 'failed', 'msg' => 'Conta criada, mas erro ao cadastrar afiliado. Tente novamente.']);
        }
        $stmt->close();

        // Marca como afiliado
        $upd = $this->conn->prepare("UPDATE customer_list SET is_affiliate = 1 WHERE id = ?");
        $upd->bind_param('i', $customer_id);
        $upd->execute();
        $upd->close();

        $this->settings->set_userdata('is_affiliate', 1);

        return json_encode(['status' => 'success', 'msg' => 'Conta criada com sucesso! Seja bem-vindo ao programa de afiliados.']);
    }

    // ─────────────────────────────────────────────────────────────────
    // ACTION: get_affiliate_link
    // Retorna o link de afiliado do usuário logado
    // ─────────────────────────────────────────────────────────────────
    public function get_affiliate_link(): string
    {
        $customer_id = $this->settings->userdata('id');

        if (!$customer_id) {
            return json_encode(['status' => 'failed', 'msg' => 'Não autenticado.']);
        }

        $stmt = $this->conn->prepare("SELECT referral_code FROM referral WHERE customer_id = ? AND status = 1 LIMIT 1");
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows === 0) {
            return json_encode(['status' => 'failed', 'msg' => 'Você não possui um link de afiliado ativo.']);
        }

        $row  = $result->fetch_assoc();
        $code = $row['referral_code'];
        $link = BASE_REF . '?&ref=' . $code;

        return json_encode(['status' => 'success', 'referral_code' => $code, 'link' => $link]);
    }

    // ─────────────────────────────────────────────────────────────────
    // ACTION: get_affiliate_info
    // Retorna dados resumidos do afiliado logado
    // ─────────────────────────────────────────────────────────────────
    public function get_affiliate_info(): string
    {
        $customer_id = $this->settings->userdata('id');

        if (!$customer_id) {
            return json_encode(['status' => 'failed', 'msg' => 'Não autenticado.']);
        }

        $stmt = $this->conn->prepare(
            "SELECT r.referral_code, r.percentage, r.amount_pending, r.amount_paid,
                    COUNT(o.id) AS total_indicacoes
             FROM referral r
             LEFT JOIN order_list o ON o.referral_id = r.referral_code
             WHERE r.customer_id = ? AND r.status = 1
             GROUP BY r.referral_code, r.percentage, r.amount_pending, r.amount_paid
             LIMIT 1"
        );
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows === 0) {
            return json_encode(['status' => 'failed', 'msg' => 'Afiliado não encontrado.']);
        }

        $row  = $result->fetch_assoc();
        $link = BASE_REF . '?&ref=' . $row['referral_code'];

        return json_encode([
            'status'            => 'success',
            'referral_code'     => $row['referral_code'],
            'link'              => $link,
            'percentage'        => $row['percentage'],
            'amount_pending'    => $row['amount_pending'],
            'amount_paid'       => $row['amount_paid'],
            'total_indicacoes'  => $row['total_indicacoes'],
        ]);
    }
}

// ─────────────────────────────────────────────────────────────────────
// Dispatcher
// ─────────────────────────────────────────────────────────────────────
$action    = strtolower($_GET['action'] ?? 'none');
$affiliate = new Affiliate();

switch ($action) {
    case 'become_affiliate':
        echo $affiliate->become_affiliate();
        break;
    case 'register_and_become_affiliate':
        echo $affiliate->register_and_become_affiliate();
        break;
    case 'get_affiliate_link':
        echo $affiliate->get_affiliate_link();
        break;
    case 'get_affiliate_info':
        echo $affiliate->get_affiliate_info();
        break;
    default:
        http_response_code(403);
        echo json_encode(['status' => 'failed', 'msg' => 'Ação inválida.']);
        break;
}
?>
