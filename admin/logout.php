<?php

/**
 * @file
 * Script para encerrar a sessão de um administrador (logout).
 * Destrói a sessão ativa e redireciona para a página de login.
 */

// 1. Inicia a sessão para poder acessá-la e manipulá-la.
session_start();

// 2. Limpa todas as variáveis da sessão.
// Atribuir um array vazio a $_SESSION remove todas as informações armazenadas,
// como 'user_id' e 'username'.
$_SESSION = array();

// 3. Destrói a sessão.
// Esta função remove a sessão do servidor, invalidando o cookie de sessão no navegador do usuário.
session_destroy();

// 4. Redireciona o usuário para a página de login.
// Após o logout, o usuário é enviado de volta para a tela de autenticação.
header("Location: login.php");
// 5. Encerra o script para garantir que o redirecionamento seja executado imediatamente.
exit();
