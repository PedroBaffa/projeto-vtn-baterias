<?php
// Ficheiro de teste de conexão com a base de dados

// Força a exibição de todos os erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Teste de Conexão com a Base de Dados</h1>";

// Tenta incluir o seu ficheiro de configuração
echo "<p>A tentar carregar o config.php...</p>";
require_once 'config.php';
echo "<p style='color:green;'>config.php carregado com sucesso!</p>";

// A variável $conn vem do seu ficheiro config.php
if (isset($conn)) {
    echo "<p style='color:green; font-weight:bold;'>A conexão com a base de dados foi bem-sucedida!</p>";
    echo "<p>Pode agora apagar este ficheiro (test_db.php) e tentar importar a sua planilha novamente.</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>ERRO: A variável de conexão (\$conn) não foi definida. Verifique o seu ficheiro config.php.</p>";
}

// Fecha a conexão
$conn = null;

?>