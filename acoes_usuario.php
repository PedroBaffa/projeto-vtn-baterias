<?php
// Arquivo: acoes_usuario.php (Com campos de endereço separados)

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'admin/config.php';

$data = json_decode(file_get_contents('php://input'), true);

// Validação
$erros = [];
if (empty($data['nome'])) $erros[] = "O campo nome é obrigatório.";
if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $erros[] = "E-mail inválido.";
if (empty($data['senha']) || strlen($data['senha']) < 6) $erros[] = "A senha deve ter no mínimo 6 caracteres.";
if (empty($data['cpf']) || strlen(preg_replace('/[^0-9]/', '', $data['cpf'])) !== 11) $erros[] = "CPF inválido.";
if (empty($data['cep']) || strlen(preg_replace('/[^0-9]/', '', $data['cep'])) !== 8) $erros[] = "CEP inválido.";
if (empty($data['endereco'])) $erros[] = "O campo endereço é obrigatório.";
if (empty($data['numero'])) $erros[] = "O campo número é obrigatório.";

if (!empty($erros)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => implode(' ', $erros)]);
    exit();
}

$senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT);

// Monta a string de endereço completa
$endereco_completo = $data['endereco'] . ', ' . $data['numero'];
if (!empty($data['complemento'])) {
    $endereco_completo .= ' - ' . $data['complemento'];
}
// Adiciona o CEP no final
$endereco_completo .= "\nCEP: " . $data['cep'];

try {
    $stmt = $conn->prepare(
        "INSERT INTO usuarios (nome, sobrenome, cpf, email, senha, telefone, endereco) 
         VALUES (:nome, :sobrenome, :cpf, :email, :senha, :telefone, :endereco)"
    );

    $stmt->execute([
        ':nome' => trim($data['nome']),
        ':sobrenome' => trim($data['sobrenome'] ?? ''),
        ':cpf' => trim($data['cpf'] ?? ''),
        ':email' => trim($data['email']),
        ':senha' => $senha_hash,
        ':telefone' => trim($data['telefone'] ?? ''),
        ':endereco' => $endereco_completo // Salva a string completa
    ]);

    $novo_usuario_id = $conn->lastInsertId();
    $_SESSION['usuario_id'] = $novo_usuario_id;
    $_SESSION['usuario_nome'] = trim($data['nome']);

    $redirect_url = 'index.html'; // Redirecionamento padrão

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Cadastro realizado com sucesso! Redirecionando...',
        'redirect' => $redirect_url
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    if ($e->errorInfo[1] == 1062) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: CPF ou E-mail já cadastrado no sistema.']);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Ocorreu um erro no servidor. Tente novamente.']);
    }
}
