<?php
// Arquivo: vtn/acoes_faq_cliente.php

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'admin/config.php';

// Verifica se o cliente está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403); // Proibido
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado. Você precisa estar logado para realizar esta ação.']);
    exit();
}

// Recebe os dados enviados pelo JavaScript
$data = json_decode(file_get_contents('php://input'), true);
$usuario_id = $_SESSION['usuario_id'];
$acao = $data['acao'] ?? '';


// AÇÃO PARA ENVIAR UMA NOVA PERGUNTA
if ($acao == 'enviar_pergunta') {
    $produto_id = $data['produto_id'] ?? 0;
    $pergunta = trim($data['pergunta'] ?? '');

    // Valida os dados
    if (empty($produto_id) || empty($pergunta)) {
        http_response_code(400); // Bad Request
        echo json_encode(['sucesso' => false, 'mensagem' => 'A pergunta não pode estar em branco.']);
        exit();
    }

    // Busca o nome e email do cliente logado
    $stmt_cliente = $conn->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
    $stmt_cliente->execute([$usuario_id]);
    $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Cliente não encontrado.']);
        exit();
    }

    // Insere a nova pergunta na base de dados
    try {
        $stmt = $conn->prepare(
            "INSERT INTO faq_perguntas (produto_id, nome_cliente, email_cliente, pergunta, status) 
             VALUES (:produto_id, :nome, :email, :pergunta, 'pendente')"
        );

        $stmt->execute([
            ':produto_id' => $produto_id,
            ':nome' => $cliente['nome'],
            ':email' => $cliente['email'],
            ':pergunta' => $pergunta
        ]);

        // Devolve uma mensagem de sucesso
        echo json_encode(['sucesso' => true, 'mensagem' => 'A sua pergunta foi enviada com sucesso! Ela será respondida em breve.']);
    } catch (PDOException $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao guardar a pergunta na base de dados.']);
    }
    exit();
}

// AÇÃO PARA MARCAR RESPOSTA COMO LIDA
if ($acao == 'marcar_como_lida') {
    $pergunta_id = $data['pergunta_id'] ?? 0;

    if (empty($pergunta_id)) {
        http_response_code(400);
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID da pergunta não fornecido.']);
        exit();
    }

    try {
        // Garante que o cliente só pode marcar as suas próprias perguntas
        $stmt = $conn->prepare("
            UPDATE faq_perguntas f
            JOIN usuarios u ON f.email_cliente = u.email
            SET f.resposta_lida = 1
            WHERE f.id = ? AND u.id = ?
        ");
        $stmt->execute([$pergunta_id, $usuario_id]);
        echo json_encode(['sucesso' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar o status da pergunta.']);
    }
    exit();
}

// Resposta padrão se nenhuma ação válida for fornecida
http_response_code(400);
echo json_encode(['sucesso' => false, 'mensagem' => 'Ação desconhecida.']);
