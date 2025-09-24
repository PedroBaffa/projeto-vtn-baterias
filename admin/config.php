<?php
// Arquivo: admin/config.php (Versão de Diagnóstico)

// --- INÍCIO: CÓDIGO TEMPORÁRIO PARA DIAGNÓSTICO ---


// Define a URL base do site (ex: http://localhost/vtn/)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $script_path = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
    $base_path = realpath(__DIR__ . '/..'); 
    $doc_root = realpath($_SERVER['DOCUMENT_ROOT']);
    $url_path = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($doc_root, '', $base_path));
    define('BASE_URL', $protocol . $host . $url_path . '/');
}

// Define a URL base do painel administrativo
if (!defined('ADMIN_URL')) {
    define('ADMIN_URL', BASE_URL . 'admin/');
}

// --- DADOS DE CONEXÃO ---
$servername = "LOCAL_SERVER";
$db_username = "USER_BD";
$db_password = "SENHA_BD";
$dbname = "NOME_BD";

// --- CRIAÇÃO DA CONEXÃO PDO ---
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Agora, em vez de 'die()', devolvemos um JSON com o erro exato.
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'ERRO DE CONEXÃO COM A BASE DE DADOS: ' . $e->getMessage()
    ]);
    exit(); // Para a execução de forma controlada
}
?>