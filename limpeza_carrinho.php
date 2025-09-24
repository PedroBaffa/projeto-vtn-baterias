<?php
// Arquivo: limpeza_carrinho.php
require_once 'admin/config.php';

echo "Iniciando limpeza de carrinhos abandonados...\n";

try {
    // Deleta itens do carrinho com mais de 5 dias
    $stmt = $conn->prepare("DELETE FROM carrinho_itens WHERE adicionado_em < NOW() - INTERVAL 5 DAY");
    $stmt->execute();

    $num_deletados = $stmt->rowCount();
    echo "Limpeza concluída. $num_deletados registros foram removidos.\n";

} catch (PDOException $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}