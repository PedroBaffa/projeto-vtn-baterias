<?php

/**
 * @file
 * session_check.php
 * Verifica o status da sessão do usuário (cliente) no front-end.
 * Retorna uma resposta JSON indicando se o usuário está logado e, em caso afirmativo, seu nome.
 * É utilizado pelo JavaScript para atualizar dinamicamente a interface do site.
 */

// Inicia a sessão para acessar as variáveis de login existentes.
session_start();
// Define o cabeçalho da resposta para indicar que o conteúdo é JSON.
header('Content-Type: application/json; charset=utf-8');

// Verifica se as variáveis de sessão 'usuario_id' e 'usuario_nome' foram definidas no login.
if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_nome'])) {
    // Se a sessão existe, retorna que o usuário está logado e seu primeiro nome.
    echo json_encode([
        'logado' => true,
        'nome' => $_SESSION['usuario_nome']
    ]);
} else {
    // Caso contrário, retorna que o usuário não está logado.
    echo json_encode(['logado' => false]);
}