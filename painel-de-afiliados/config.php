<?php
/**
 * Config do Painel de Afiliados
 * Conexão DB + sessão independente do settings.php
 */

session_start();
ini_set('display_errors', 0);
error_reporting(0);
date_default_timezone_set('America/Sao_Paulo');

// Carrega constantes do projeto (DB_SERVER, DB_NAME, BASE_URL, etc)
require_once(dirname(__DIR__) . '/initialize.php');

// Conexão própria com o banco
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'msg' => 'Erro de conexão com o banco.']));
}
$conn->set_charset('utf8mb4');

// Helpers de sessão do afiliado
function aff_logged_in() {
    return !empty($_SESSION['aff_user']['id']);
}

function aff_user($field = null) {
    if (!aff_logged_in()) return null;
    if ($field) return isset($_SESSION['aff_user'][$field]) ? $_SESSION['aff_user'][$field] : null;
    return $_SESSION['aff_user'];
}

function aff_login($user_data) {
    $_SESSION['aff_user'] = $user_data;
}

function aff_logout() {
    unset($_SESSION['aff_user']);
}
