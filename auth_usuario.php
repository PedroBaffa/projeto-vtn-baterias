<?php

/**
 * @file
 * auth_usuario.php
 * Processa a tentativa de login de um usuário (cliente) do site.
 * Recebe os dados via JSON, valida as credenciais e, em caso de sucesso,
 * cria uma sessão de login e retorna uma URL para redirecionamento.
 */

// Inicia a sessão para armazenar as informações do usuário logado.
session_start();
// Define o cabeçalho da resposta para indicar que o conteúdo é JSON.
header('Content-Type: application/json; charset=utf-8');
// Inclui o arquivo de configuração para a conexão com o banco de dados.
require_once 'admin/config.php';

// Recebe os dados em formato JSON enviados pelo JavaScript (fetch).
$data = json_decode(file_get_contents('php://input'), true);

// --- VALIDAÇÃO DOS DADOS DE ENTRADA ---
if (empty($data['email']) || empty($data['senha'])) {
    // Se e-mail ou senha não forem enviados, retorna um erro 400 (Bad Request).
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'E-mail e senha são obrigatórios.']);
    exit();
}

try {
    // --- BUSCA E VERIFICAÇÃO DO USUÁRIO ---
    // Busca no banco de dados um usuário com o e-mail fornecido.
    $stmt = $conn->prepare("SELECT id, nome, senha FROM usuarios WHERE email = :email");
    $stmt->execute([':email' => $data['email']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica se um usuário foi encontrado E se a senha fornecida corresponde ao hash no banco de dados.
    // password_verify() é a função segura para esta comparação.
    if ($usuario && password_verify($data['senha'], $usuario['senha'])) {

        // --- SUCESSO NO LOGIN ---
        // Armazena o ID e o nome do usuário na sessão, efetivando o login.
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];

        // --- LÓGICA DE REDIRECIONAMENTO ---
        // Pega a URL de redirecionamento enviada pelo front-end (login.js).
        // Se nenhuma URL for especificada, o padrão é 'index.html'.
        $redirect_url = $data['redirect'] ?? 'index.html';

        // Retorna uma resposta de sucesso para o front-end, incluindo a URL de destino.
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Login efetuado com sucesso! Redirecionando...',
            'redirect' => $redirect_url
        ]);
    } else {
        // --- FALHA NO LOGIN ---
        // Se o usuário não for encontrado ou a senha estiver incorreta, retorna um erro 401 (Unauthorized).
        http_response_code(401);
        echo json_encode(['sucesso' => false, 'mensagem' => 'E-mail ou senha inválidos.']);
    }
} catch (PDOException $e) {
    // --- ERRO DE SERVIDOR ---
    // Em caso de erro na conexão ou na query com o banco de dados.
    http_response_code(500);
    // Em produção, o ideal é registrar $e->getMessage() em um log.
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no servidor.']);
}
