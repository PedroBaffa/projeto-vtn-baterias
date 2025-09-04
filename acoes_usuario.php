<?php

/**
 * @file
 * acoes_usuario.php
 * Processa o cadastro de novos usuários (clientes) no site.
 * Recebe os dados do formulário de cadastro, valida, insere no banco de dados
 * e inicia uma sessão para o novo usuário (login automático).
 * Responde em formato JSON.
 */

// Inicia a sessão para poder criar as variáveis de login após o cadastro.
session_start();
// Define o cabeçalho da resposta como JSON.
header('Content-Type: application/json; charset=utf-8');
// Inclui o arquivo de configuração para a conexão com o banco de dados.
require_once 'admin/config.php';

// Recebe os dados em formato JSON enviados pelo JavaScript (fetch).
$data = json_decode(file_get_contents('php://input'), true);

// --- VALIDAÇÃO DOS DADOS ---
// Verifica se os campos obrigatórios foram preenchidos e se são válidos.
$erros = [];
if (empty($data['nome']))
    $erros[] = "O campo nome é obrigatório.";
if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL))
    $erros[] = "E-mail inválido.";
if (empty($data['senha']) || strlen($data['senha']) < 6)
    $erros[] = "A senha deve ter no mínimo 6 caracteres.";

// Se houver algum erro de validação, retorna uma resposta de erro 400 (Bad Request).
if (!empty($erros)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => implode(' ', $erros)]);
    exit();
}

// --- PROCESSAMENTO E INSERÇÃO NO BANCO ---

// Medida de segurança CRÍTICA: Criptografa a senha do usuário usando o algoritmo padrão e mais seguro do PHP.
// Isso garante que a senha nunca seja armazenada em texto plano no banco de dados.
$senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT);

try {
    // Prepara a instrução SQL para inserir o novo usuário.
    $stmt = $conn->prepare(
        "INSERT INTO usuarios (nome, sobrenome, cpf, email, senha, telefone, endereco) 
         VALUES (:nome, :sobrenome, :cpf, :email, :senha, :telefone, :endereco)"
    );

    // Executa a query, associando os dados recebidos aos placeholders.
    // A função trim() remove espaços em branco extras do início e do fim das strings.
    $stmt->execute([
        ':nome' => trim($data['nome']),
        ':sobrenome' => trim($data['sobrenome'] ?? ''),
        ':cpf' => trim($data['cpf'] ?? ''),
        ':email' => trim($data['email']),
        ':senha' => $senha_hash,
        ':telefone' => trim($data['telefone'] ?? ''),
        ':endereco' => trim($data['endereco'] ?? null)
    ]);

    // --- LOGIN AUTOMÁTICO APÓS CADASTRO ---
    // Após o cadastro bem-sucedido, inicia a sessão para o novo usuário.
    $novo_usuario_id = $conn->lastInsertId(); // Pega o ID do usuário recém-criado.
    $_SESSION['usuario_id'] = $novo_usuario_id;
    $_SESSION['usuario_nome'] = trim($data['nome']);

    // Define a URL para a qual o usuário será redirecionado.
    $redirect_url = $data['redirect'] ?? 'index.html';

    // Retorna uma resposta de sucesso para o front-end, incluindo a URL de redirecionamento.
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Cadastro realizado com sucesso! Redirecionando...',
        'redirect' => $redirect_url
    ]);
} catch (PDOException $e) {
    // --- TRATAMENTO DE ERROS DO BANCO DE DADOS ---

    // Define o código de erro HTTP para 500 (Internal Server Error).
    http_response_code(500);

    // Verifica se o erro foi de duplicidade (código 1062 do MySQL),
    // o que significa que o CPF ou o e-mail já existem.
    if ($e->errorInfo[1] == 1062) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: CPF ou E-mail já cadastrado no sistema.']);
    } else {
        // Para outros tipos de erro, retorna uma mensagem genérica.
        // Em um ambiente de produção, o erro $e->getMessage() deveria ser registrado em um log.
        echo json_encode(['sucesso' => false, 'mensagem' => 'Ocorreu um erro no servidor. Tente novamente.']);
    }
}
