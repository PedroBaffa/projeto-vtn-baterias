<?php
// Arquivo: admin/logout.php (Verificado e Correto)

// Inicia a sessão para poder acedê-la
session_start();

// Destrói todas as variáveis da sessão
$_SESSION = array();

// Destrói a sessão
session_destroy();

// Redireciona para a página de login. O caminho está correto.
header("Location: login.php");
exit();
