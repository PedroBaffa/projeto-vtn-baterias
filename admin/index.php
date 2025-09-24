<?php
// Arquivo: admin/index.php (Verificado e Correto)

// Inicia a sessão para verificar o status de login
session_start();

// Se o usuário já tiver uma sessão ativa (estiver logado),
// redireciona para o painel principal (visao_geral.php). O caminho está correto.
if (isset($_SESSION['user_id'])) {
    header("Location: visao_geral.php");
    exit();
} else {
    // Se não estiver logado, redireciona para a página de login. O caminho está correto.
    header("Location: login.php");
    exit();
}
