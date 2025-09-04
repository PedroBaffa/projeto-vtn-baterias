<?php

/**
 * @file
 * Ponto de entrada para o diretório de administração.
 * A única função deste script é verificar se o usuário já está logado.
 */

// Inicia a sessão para poder acessar as variáveis de sessão (como $_SESSION['user_id']).
session_start();

// Verifica se a variável de sessão 'user_id' foi definida durante o login.
if (isset($_SESSION['user_id'])) {
    // Se o usuário já tiver uma sessão ativa (estiver logado),
    // ele é imediatamente redirecionado para a página principal do painel (visao_geral.php).
    // Isso impede que um usuário logado veja a página de login novamente.
    header("Location: visao_geral.php");
    exit(); // Encerra o script para garantir que o redirecionamento ocorra.
} else {
    // Se o usuário não estiver logado (nenhuma sessão ativa encontrada),
    // ele é redirecionado para a página de login para se autenticar.
    header("Location: login.php");
    exit(); // Encerra o script.
}
