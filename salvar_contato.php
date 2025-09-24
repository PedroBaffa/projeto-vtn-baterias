<?php
// Arquivo: salvar_contato.php

header('Content-Type: application/json; charset=utf-8');
require_once 'admin/config.php'; // Usa nossa conexão com o banco

// Pega os dados enviados pelo JavaScript (em formato JSON)
$data = json_decode(file_get_contents('php://input'), true);

// Validação simples dos dados
if (empty($data['nome']) || empty($data['email']) || empty($data['whatsapp'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['sucesso' => false, 'mensagem' => 'Todos os campos são obrigatórios.']);
    exit();
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Por favor, insira um e-mail válido.']);
    exit();
}

// Se os dados são válidos, vamos inserir no banco
try {
    $stmt = $conn->prepare(
        "INSERT INTO contatos (nome, email, whatsapp) VALUES (:nome, :email, :whatsapp)"
    );

    $stmt->execute([
        ':nome' => trim($data['nome']),
        ':email' => trim($data['email']),
        ':whatsapp' => trim($data['whatsapp'])
    ]);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Contato salvo com sucesso!']);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    // Em um ambiente de produção, seria bom logar o erro em vez de exibi-lo
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar no banco de dados.']);
}