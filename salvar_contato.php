<?php

/**
 * @file
 * salvar_contato.php
 * Processa o envio do formulário de contato (lead) do site.
 * Recebe os dados via JSON, valida e os insere no banco de dados.
 */

// Define o cabeçalho da resposta como JSON com codificação UTF-8.
header('Content-Type: application/json; charset=utf-8');
// Inclui o arquivo de configuração para a conexão com o banco de dados.
require_once 'admin/config.php';

// Pega os dados enviados pelo JavaScript (em formato JSON) do corpo da requisição.
$data = json_decode(file_get_contents('php://input'), true);

// Validação simples para garantir que os campos essenciais não estão vazios.
if (empty($data['nome']) || empty($data['email']) || empty($data['whatsapp'])) {
    http_response_code(400); // Retorna o código de erro "Bad Request".
    echo json_encode(['sucesso' => false, 'mensagem' => 'Todos os campos são obrigatórios.']);
    exit();
}

// Valida o formato do e-mail.
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400); // Retorna o código de erro "Bad Request".
    echo json_encode(['sucesso' => false, 'mensagem' => 'Por favor, insira um e-mail válido.']);
    exit();
}

// Se os dados são válidos, tenta inserir no banco.
try {
    // Prepara a instrução SQL para inserir o novo contato.
    $stmt = $conn->prepare(
        "INSERT INTO contatos (nome, email, whatsapp) VALUES (:nome, :email, :whatsapp)"
    );

    // Executa a query, associando os dados recebidos aos placeholders.
    // A função trim() remove espaços em branco do início e do fim.
    $stmt->execute([
        ':nome' => trim($data['nome']),
        ':email' => trim($data['email']),
        ':whatsapp' => trim($data['whatsapp'])
    ]);

    // Retorna uma resposta de sucesso.
    echo json_encode(['sucesso' => true, 'mensagem' => 'Contato salvo com sucesso!']);
} catch (PDOException $e) {
    http_response_code(500); // Retorna o código "Internal Server Error".
    // Em um ambiente de produção, seria ideal registrar o erro em um log em vez de exibi-lo.
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar no banco de dados.']);
}
