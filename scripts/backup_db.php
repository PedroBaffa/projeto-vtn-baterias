<?php
// Script de Backup Automático para o Banco de Dados do Grupo VTN

// --- CONFIGURAÇÕES ---

// Para segurança, vamos buscar as credenciais do nosso arquivo de configuração principal.
// O '..' volta um diretório para chegar na pasta 'admin'.
require_once __DIR__ . '/../config.php';

// Diretório onde os backups serão salvos (dentro da pasta 'admin').
// É CRUCIAL proteger este diretório! Veremos isso mais à frente.
$backup_dir = __DIR__ . '/backups/';

// Número de dias que os backups devem ser mantidos. Arquivos mais antigos serão apagados.
$dias_para_manter = 7;


// --- LÓGICA DO SCRIPT ---

echo "Iniciando processo de backup...\n";

// 1. Garante que o diretório de backups exista
if (!is_dir($backup_dir)) {
    if (!mkdir($backup_dir, 0755, true)) {
        die("ERRO: Não foi possível criar o diretório de backups em: " . $backup_dir . "\n");
    }

    // Adiciona um arquivo .htaccess para proteger o diretório
    $htaccess_content = "deny from all";
    file_put_contents($backup_dir . '.htaccess', $htaccess_content);
    echo "Diretório de backups criado e protegido com sucesso.\n";
}

// 2. Define o nome do arquivo de backup com data e hora
// Formato: backup-grupo_vtn_db-2025-08-19_14-30-00.sql.gz
$backup_file_name = "backup-" . $dbname . "-" . date("Y-m-d_H-i-s") . ".sql.gz";
$backup_file_path = $backup_dir . $backup_file_name;

// 3. Monta o comando 'mysqldump' para gerar o backup e comprimir com gzip
// Este comando é executado diretamente no servidor. É a forma mais eficiente de fazer backups.
$command = sprintf(
    'mysqldump --user=%s --password=%s --host=%s %s | gzip > %s',
    escapeshellarg($db_username),
    escapeshellarg($db_password),
    escapeshellarg($servername),
    escapeshellarg($dbname),
    escapeshellarg($backup_file_path)
);

// 4. Executa o comando
$output = null;
$return_var = null;
exec($command, $output, $return_var);

// 5. Verifica se o backup foi bem-sucedido
if ($return_var === 0) {
    echo "Backup do banco de dados '" . $dbname . "' criado com sucesso em: " . $backup_file_path . "\n";
} else {
    die("ERRO: Falha ao criar o backup. Código de retorno: " . $return_var . "\n");
}

// 6. Limpa backups antigos
echo "Limpando backups antigos (mantendo os dos últimos " . $dias_para_manter . " dias)...\n";
$files = glob($backup_dir . "*.sql.gz");
$now = time();

foreach ($files as $file) {
    if (is_file($file)) {
        if ($now - filemtime($file) >= ($dias_para_manter * 86400)) { // 86400 segundos = 1 dia
            unlink($file);
            echo "Backup antigo removido: " . basename($file) . "\n";
        }
    }
}

echo "Processo de backup concluído com sucesso!\n";
?>