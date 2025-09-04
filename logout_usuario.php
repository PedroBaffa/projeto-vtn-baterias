<?php
// Arquivo: logout_usuario.php (Versão Corrigida e Simplificada)

session_start();

// Define a URL padrão para o caso de nada ser passado
$redirect_url = 'index.html';

// Se uma URL foi passada como parâmetro GET, vamos usá-la
if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    // Decodifica a URL que foi enviada pelo JavaScript
    $decoded_url = urldecode($_GET['redirect']);
    
    // Simplesmente usamos a URL decodificada
    $redirect_url = $decoded_url;
}

// Limpa todas as variáveis da sessão
session_unset();

// Destrói a sessão
session_destroy();

// Redireciona o usuário para a URL correta
header('Location: ' . $redirect_url);
exit();