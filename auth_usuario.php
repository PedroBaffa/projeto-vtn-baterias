<?php
// Arquivo: auth_usuario.php (Versão Corrigida)
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'admin/config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['email']) || empty($data['senha'])) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'E-mail e senha são obrigatórios.']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT id, nome, senha FROM usuarios WHERE email = :email");
    $stmt->execute([':email' => $data['email']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($data['senha'], $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];

        // *** LÓGICA DE REDIRECIONAMENTO MODIFICADA ***
        // Pega a URL do corpo da requisição. Se não existir, usa 'index.html'.
        $redirect_url = $data['redirect'] ?? 'index.html';

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Login efetuado com sucesso! Redirecionando...',
            'redirect' => $redirect_url // Envia a URL de volta
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['sucesso' => false, 'mensagem' => 'E-mail ou senha inválidos.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no servidor.']);
}