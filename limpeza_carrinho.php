<?php

/**
 * @file
 * limpeza_carrinho.php
 * Script de manutenção para limpar carrinhos de compras abandonados.
 * Projetado para ser executado periodicamente (ex: via CRON job) para
 * manter o banco de dados limpo e otimizado.
 */

// Inclui o arquivo de configuração para a conexão com o banco de dados.
require_once 'admin/config.php';

echo "Iniciando limpeza de carrinhos abandonados...\n";

try {
    // Prepara uma instrução SQL para deletar todos os itens da tabela 'carrinho_itens'
    // que foram adicionados há mais de 5 dias.
    $stmt = $conn->prepare("DELETE FROM carrinho_itens WHERE adicionado_em < NOW() - INTERVAL 5 DAY");
    $stmt->execute();

    // Informa quantos registros foram removidos.
    $num_deletados = $stmt->rowCount();
    echo "Limpeza concluída. $num_deletados registros foram removidos.\n";
} catch (PDOException $e) {
    // Em caso de erro, exibe a mensagem no console.
    echo "ERRO: " . $e->getMessage() . "\n";
}
