<?php

/**
 * @file
 * Arquivo de configuração central para a conexão com o banco de dados.
 * Este arquivo é incluído em todos os outros scripts que precisam acessar o banco.
 *
 * ATENÇÃO: Este arquivo contém informações sensíveis.
 * NUNCA suba suas credenciais reais (usuário, senha) para um repositório público.
 * O ideal é usar variáveis de ambiente em um servidor de produção.
 */

// --- DADOS DE CONEXÃO ---

// Endereço do servidor do banco de dados. Para desenvolvimento local (XAMPP, WAMP), geralmente é "localhost".
$servername = "localhost";

// Nome de usuário para acessar o banco de dados.
$db_username = "seu_usuario_de_banco_de_dados";

// Senha para o usuário do banco de dados.
$db_password = "sua_senha_de_banco_de_dados";

// Nome do banco de dados que o projeto utilizará.
$dbname = "grupo_vtn_db";


// --- CRIAÇÃO DA CONEXÃO PDO ---

try {
    // Tenta estabelecer a conexão com o banco de dados usando PDO (PHP Data Objects).
    // PDO é a forma moderna e segura de se conectar a bancos de dados em PHP, pois facilita o uso de prepared statements.
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $db_username, $db_password);

    // Define o modo de erro do PDO para exceções. Isso significa que, se ocorrer um erro na query,
    // o PDO lançará uma exceção, o que permite um tratamento de erros mais robusto com blocos try-catch.
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Se a conexão falhar, o script é interrompido e uma mensagem de erro fatal é exibida.
    // Em um ambiente de produção, seria melhor registrar este erro em um log em vez de exibi-lo na tela.
    die("Erro fatal na conexão com o banco de dados local: " . $e->getMessage());
}
