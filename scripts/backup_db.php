<?php

/**
 * @file
 * Script de Backup Automático para o Banco de Dados do Grupo VTN.
 * Este script foi projetado para ser executado via linha de comando (CLI) ou
 * por um agendador de tarefas (CRON job) no servidor.
 * Ele cria um backup compactado do banco de dados e apaga backups antigos.
 */

// --- CONFIGURAÇÕES ---

// Para segurança, busca as credenciais do nosso arquivo de configuração principal.
// __DIR__ . '/../admin/config.php' garante que o caminho para o arquivo de configuração
// seja sempre encontrado, independentemente de onde o script de backup for executado.
require_once __DIR__ . '/../admin/config.php';

// Diretório onde os backups serão salvos.
// É uma boa prática manter os backups fora da pasta pública do site.
$backup_dir = __DIR__ . '/backups/';

// Número de dias que os backups devem ser mantidos. Arquivos mais antigos que isso serão apagados.
$dias_para_manter = 7;


// --- LÓGICA DO SCRIPT ---

// Imprime o status no console (útil para logs).
echo "Iniciando processo de backup...\n";

// 1. Garante que o diretório de backups exista.
if (!is_dir($backup_dir)) {
    // Tenta criar o diretório com as permissões corretas.
    if (!mkdir($backup_dir, 0755, true)) {
        die("ERRO: Não foi possível criar o diretório de backups em: " . $backup_dir . "\n");
    }

    // Medida de segurança CRÍTICA: Adiciona um arquivo .htaccess para
    // bloquear o acesso direto aos arquivos de backup via navegador.
    $htaccess_content = "deny from all";
    file_put_contents($backup_dir . '.htaccess', $htaccess_content);
    echo "Diretório de backups criado e protegido com sucesso.\n";
}

// 2. Define o nome do arquivo de backup com data e hora para fácil identificação.
// Exemplo: backup-grupo_vtn_db-2025-09-04_15-22-00.sql.gz
$backup_file_name = "backup-" . $dbname . "-" . date("Y-m-d_H-i-s") . ".sql.gz";
$backup_file_path = $backup_dir . $backup_file_name;

// 3. Monta o comando do sistema para executar o backup.
// Utiliza a ferramenta 'mysqldump', que é o padrão para backups MySQL.
// O resultado é "pipado" (|) para o 'gzip', que compacta o arquivo para economizar espaço.
$command = sprintf(
    'mysqldump --user=%s --password=%s --host=%s %s | gzip > %s',
    // escapeshellarg() é uma função de segurança vital que previne a injeção de comandos
    // maliciosos, garantindo que as credenciais sejam tratadas como strings seguras.
    escapeshellarg($db_username),
    escapeshellarg($db_password),
    escapeshellarg($servername),
    escapeshellarg($dbname),
    escapeshellarg($backup_file_path)
);

// 4. Executa o comando no shell do servidor.
$output = null;
$return_var = null;
exec($command, $output, $return_var);

// 5. Verifica se o comando foi executado com sucesso.
// Um código de retorno '0' geralmente significa sucesso na linha de comando.
if ($return_var === 0) {
    echo "Backup do banco de dados '" . $dbname . "' criado com sucesso em: " . $backup_file_path . "\n";
} else {
    // Se houver um erro, interrompe o script e informa a falha.
    die("ERRO: Falha ao criar o backup. Código de retorno: " . $return_var . "\n");
}

// 6. Limpa backups antigos para não sobrecarregar o disco do servidor.
echo "Limpando backups antigos (mantendo os dos últimos " . $dias_para_manter . " dias)...\n";
$files = glob($backup_dir . "*.sql.gz"); // Pega todos os arquivos de backup.
$now = time(); // Pega o timestamp atual.

foreach ($files as $file) {
    if (is_file($file)) {
        // Compara a data de modificação do arquivo com a data atual.
        if ($now - filemtime($file) >= ($dias_para_manter * 86400)) { // 86400 segundos = 1 dia
            unlink($file); // Apaga o arquivo.
            echo "Backup antigo removido: " . basename($file) . "\n";
        }
    }
}

echo "Processo de backup concluído com sucesso!\n";
