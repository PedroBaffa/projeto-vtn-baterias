<?php
// Arquivo: admin/limpeza_galeria.php
// Este script foi feito para ser executado automaticamente (via Cron Job)

require_once 'config.php';

echo "--- Iniciando limpeza da galeria temporária em " . date('Y-m-d H:i:s') . " ---\n";

try {
    $conn->beginTransaction();

    // 1. Encontrar todos os grupos expirados
    $stmt_expirados = $conn->prepare("SELECT id FROM galeria_grupos WHERE data_expiracao < CURDATE()");
    $stmt_expirados->execute();
    $grupos_expirados_ids = $stmt_expirados->fetchAll(PDO::FETCH_COLUMN, 0);

    if (empty($grupos_expirados_ids)) {
        echo "Nenhum grupo expirado para apagar. Finalizando.\n";
        $conn->commit();
        exit();
    }

    echo "Encontrados " . count($grupos_expirados_ids) . " grupos expirados. IDs: " . implode(', ', $grupos_expirados_ids) . "\n";

    // 2. Apagar os arquivos físicos do servidor
    $placeholders = implode(',', array_fill(0, count($grupos_expirados_ids), '?'));

    $stmt_paths = $conn->prepare("SELECT image_path FROM galeria_imagens WHERE grupo_id IN ($placeholders)");
    $stmt_paths->execute($grupos_expirados_ids);
    $paths_to_delete = $stmt_paths->fetchAll(PDO::FETCH_COLUMN, 0);

    $arquivos_apagados = 0;
    foreach ($paths_to_delete as $path) {
        $full_path = '../' . $path;
        if (file_exists($full_path)) {
            if (unlink($full_path)) {
                $arquivos_apagados++;
            }
        }
    }
    echo "$arquivos_apagados arquivos físicos foram apagados do servidor.\n";

    // 3. Apagar os grupos do banco de dados
    // A regra "ON DELETE CASCADE" irá apagar as entradas em `galeria_imagens` automaticamente
    $stmt_delete_grupos = $conn->prepare("DELETE FROM galeria_grupos WHERE id IN ($placeholders)");
    $stmt_delete_grupos->execute($grupos_expirados_ids);

    $grupos_apagados = $stmt_delete_grupos->rowCount();
    echo "$grupos_apagados grupos foram apagados do banco de dados.\n";

    $conn->commit();
    echo "--- Limpeza concluída com sucesso! ---\n";
} catch (PDOException $e) {
    $conn->rollBack();
    echo "ERRO DURANTE A LIMPEZA: " . $e->getMessage() . "\n";
}
