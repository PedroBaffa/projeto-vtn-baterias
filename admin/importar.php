<?php
// Arquivo: admin/importar.php (Versão Refeita)

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$username = htmlspecialchars($_SESSION['username']);
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Importar Produtos via CSV - Painel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        #log-container {
            height: 250px;
            background-color: #1a202c;
            color: #a0aec0;
            font-family: monospace;
            font-size: 0.8rem;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-y: auto;
            white-space: pre-wrap;
        }

        #log-container p {
            margin: 0;
            padding-bottom: 2px;
        }

        .log-success {
            color: #48bb78;
        }

        .log-error {
            color: #f56565;
        }

        .log-info {
            color: #4299e1;
        }

        .log-summary {
            font-weight: bold;
            color: white;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <?php require_once 'templates/sidebar.php'; ?>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700">Importar Produtos em Massa</h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">Sair</a>
                </div>
            </header>

            <main class="flex-1 p-6">
                <div class="bg-white shadow-md rounded-lg p-8 max-w-4xl mx-auto">

                    <div id="upload-section">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">1. Envie seu Arquivo CSV</h2>
                        <div class="prose prose-sm max-w-none bg-gray-50 p-4 rounded-lg border mb-6">
                            <p>O arquivo CSV deve ter um cabeçalho e seguir a ordem de colunas exata:</p>
                            <code class="text-sm font-semibold">brand,title,sku,price,capacity,condicao,descricao,images</code>
                            <ul class="text-xs mt-2">
                                <li><strong>condicao:</strong> Use 'novo' ou 'retirado'.</li>
                                <li><strong>images:</strong> Separe múltiplos links de imagem com vírgula (ex: "path/1.jpg, path/2.jpg").</li>
                                <li>Se um SKU já existir, o produto e suas imagens serão <strong>completamente atualizados</strong> com os dados do arquivo.</li>
                            </ul>
                        </div>
                        <form id="upload-form">
                            <input type="file" id="csv_file" name="csv_file" required accept=".csv, text/csv"
                                class="w-full max-w-md text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                            <div class="mt-6">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition disabled:opacity-50" id="analise-btn">
                                    <i class="fas fa-cogs mr-2"></i> Analisar Arquivo
                                </button>
                            </div>
                        </form>
                    </div>

                    <div id="processing-section" class="hidden">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">2. Confirme e Inicie a Importação</h2>
                        <div id="summary" class="p-4 bg-blue-50 border border-blue-200 rounded-lg mb-4 text-gray-800"></div>

                        <div id="progress-container" class="mb-4 hidden">
                            <div class="flex justify-between mb-1">
                                <span class="text-base font-medium text-gray-700">Progresso da Importação</span>
                                <span class="text-sm font-medium text-gray-700" id="progress-text">0%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-4">
                                <div id="progress-bar" class="bg-green-500 h-4 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                        </div>

                        <div id="log-container" class="mb-6 hidden"></div>

                        <div id="action-buttons" class="flex items-center gap-4">
                            <button id="confirm-btn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition">
                                <i class="fas fa-check mr-2"></i> Confirmar e Iniciar
                            </button>
                            <button id="cancel-btn" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg transition">
                                <i class="fas fa-times mr-2"></i> Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Referências aos elementos da página
            const uploadSection = document.getElementById('upload-section');
            const processingSection = document.getElementById('processing-section');
            const uploadForm = document.getElementById('upload-form');
            const csvFileInput = document.getElementById('csv_file');
            const analiseBtn = document.getElementById('analise-btn');

            const summaryDiv = document.getElementById('summary');
            const confirmBtn = document.getElementById('confirm-btn');
            const cancelBtn = document.getElementById('cancel-btn');
            const progressContainer = document.getElementById('progress-container');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const logContainer = document.getElementById('log-container');
            const actionButtons = document.getElementById('action-buttons');

            let importData = []; // Armazena os dados do CSV analisado
            let isCancelled = false;
            let isProcessing = false;

            // 1. ANÁLISE DO ARQUIVO CSV
            uploadForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (csvFileInput.files.length === 0) {
                    alert('Por favor, selecione um arquivo CSV.');
                    return;
                }

                analiseBtn.disabled = true;
                analiseBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Analisando...';

                const formData = new FormData();
                formData.append('csv_file', csvFileInput.files[0]);
                formData.append('acao', 'analisar_csv');

                try {
                    const response = await fetch('actions/acoes_produto.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Erro desconhecido no servidor.');
                    }

                    importData = result.rows_data;
                    const summary = result.summary;
                    summaryDiv.innerHTML = `
                        <p class="font-bold">Arquivo analisado com sucesso!</p>
                        <ul class="list-disc list-inside mt-2">
                            <li><strong class="text-green-600">${summary.create_count}</strong> produtos serão CRIADOS.</li>
                            <li><strong class="text-yellow-600">${summary.update_count}</strong> produtos serão ATUALIZADOS.</li>
                            <li class="mt-2"><strong>Total de ${summary.total_count} linhas a serem processadas.</strong></li>
                        </ul>`;

                    uploadSection.classList.add('hidden');
                    processingSection.classList.remove('hidden');

                    if (summary.total_count === 0) {
                        confirmBtn.disabled = true;
                        confirmBtn.innerHTML = 'Nenhuma linha para importar';
                    }

                } catch (error) {
                    alert(`Erro na análise: ${error.message}`);
                    analiseBtn.disabled = false;
                    analiseBtn.innerHTML = '<i class="fas fa-cogs mr-2"></i> Analisar Arquivo';
                }
            });

            // 2. CONFIRMAÇÃO E INÍCIO DA IMPORTAÇÃO
            confirmBtn.addEventListener('click', () => {
                isCancelled = false;
                isProcessing = true;
                progressContainer.classList.remove('hidden');
                logContainer.classList.remove('hidden');
                logContainer.innerHTML = '';

                confirmBtn.classList.add('hidden');
                cancelBtn.classList.remove('bg-gray-500', 'hover:bg-gray-600');
                cancelBtn.classList.add('bg-red-600', 'hover:bg-red-700');
                cancelBtn.innerHTML = '<i class="fas fa-stop-circle mr-2"></i> Parar Operação';

                processImportQueue();
            });

            // 3. BOTÃO DE CANCELAR / REINICIAR
            cancelBtn.addEventListener('click', () => {
                if (isProcessing) {
                    isCancelled = true;
                    cancelBtn.disabled = true;
                    cancelBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Parando...';
                    addToLog('Operação de cancelamento solicitada...', 'log-error');
                } else {
                    location.reload(); // Se não está processando, apenas recarrega a página.
                }
            });

            // 4. LÓGICA DE PROCESSAMENTO EM FILA
            async function processImportQueue() {
                let successCount = 0;
                let errorCount = 0;
                const total = importData.length;

                addToLog('Iniciando a importação...', 'log-info');

                for (let i = 0; i < total; i++) {
                    if (isCancelled) {
                        addToLog('Processo interrompido pelo usuário.', 'log-error');
                        break;
                    }
                    const row = importData[i];
                    try {
                        const response = await fetch('actions/acoes_produto.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                ...row,
                                acao: 'importar_linha_csv'
                            })
                        });
                        const result = await response.json();
                        if (!response.ok || !result.success) {
                            throw new Error(result.message);
                        }
                        addToLog(`[${i + 1}/${total}] SUCESSO: SKU ${result.sku}`, 'log-success');
                        successCount++;
                    } catch (error) {
                        addToLog(`[${i + 1}/${total}] ERRO: SKU ${row.sku} - ${error.message}`, 'log-error');
                        errorCount++;
                    }

                    // Atualiza a barra de progresso
                    const percent = Math.round(((i + 1) / total) * 100);
                    progressBar.style.width = `${percent}%`;
                    progressText.textContent = `${percent}% (${i + 1}/${total})`;
                }

                // Finalização
                isProcessing = false;
                let finalMessage = isCancelled ? 'Importação cancelada.' : 'Importação Concluída!';
                addToLog('-------------------------', 'log-summary');
                addToLog(finalMessage, 'log-summary');
                addToLog(`Sucesso: ${successCount}`, 'log-success');
                addToLog(`Erros: ${errorCount}`, 'log-error');

                cancelBtn.classList.remove('bg-red-600', 'hover:bg-red-700');
                cancelBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                cancelBtn.innerHTML = '<i class="fas fa-upload mr-2"></i> Importar Novo Arquivo';
                cancelBtn.disabled = false;
            }

            // Função auxiliar para adicionar mensagens ao log
            function addToLog(message, className = '') {
                logContainer.innerHTML += `<p class="${className}">> ${message}</p>`;
                logContainer.scrollTop = logContainer.scrollHeight;
            }
        });
    </script>
</body>

</html>