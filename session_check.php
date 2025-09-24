<?php
// Arquivo: session_check.php (Com contagem de notificações)

session_start();
header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_nome'])) {
    require_once 'admin/config.php';

    // [NOVO] Conta quantas respostas o usuário ainda não leu
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM faq_perguntas f
        JOIN usuarios u ON f.email_cliente = u.email
        WHERE u.id = ? AND f.resposta IS NOT NULL AND f.resposta_lida = 0
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $unread_answers = $stmt->fetchColumn();

    // Se a sessão existe, retorna que o usuário está logado, seu nome e a contagem de notificações
    echo json_encode([
        'logado' => true,
        'nome' => $_SESSION['usuario_nome'],
        'unread_answers' => (int)$unread_answers
    ]);
} else {
    // Caso contrário, retorna que não está logado
    echo json_encode(['logado' => false]);
}
