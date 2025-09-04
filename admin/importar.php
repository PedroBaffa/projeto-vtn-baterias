<?php

/**
 * @file
 * Página do painel de administração para importação de produtos em massa via arquivo CSV.
 * Fornece as instruções de formato do arquivo e o formulário para upload.
 */

session_start();
// Medida de segurança: Garante que apenas usuários logados possam acessar.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtém e sanitiza o nome de usuário da sessão para exibição.
$username = htmlspecialchars($_SESSION['username']);

// Verifica se há mensagens de erro ou sucesso na URL (retornadas pelo script de processamento) e as prepara para exibição.
$error_msg = isset($_GET['err']) ? htmlspecialchars($_GET['err']) : '';
$success_msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Importar Produtos - Painel Administrativo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <div class="w-64 bg-white shadow-md sticky top-0 h-screen">
            <div class="p-6 text-center border-b"><img src="../assets/img/logo.png" alt="Logo VTN" class="mx-auto h-12">
            </div>
            <nav class="mt-4">
                <a href="visao_geral.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-chart-pie w-6 text-center"></i><span class="mx-3">Visão Geral</span></a>
                <a href="dashboard.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-box-open w-6 text-center"></i><span class="mx-3">Produtos</span></a>
                <a href="importar.php" class="flex items-center py-2 px-6 bg-gray-200 text-gray-800 font-semibold"><i class="fas fa-file-upload w-6 text-center"></i><span class="mx-3">Importar CSV</span></a>
                <a href="admins.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-users-cog w-6 text-center"></i><span class="mx-3">Admins</span></a>
                <a href="galeria.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-images w-6 text-center"></i><span class="mx-3">Galeria</span></a>
                <a href="contatos.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-address-book w-6 text-center"></i><span class="mx-3">Contatos</span></a>
                <a href="usuarios.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-users w-6 text-center"></i><span class="mx-3">Usuários</span></a>
                <a href="promocoes.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200">
                    <i class="fas fa-tags w-6 text-center"></i><span class="mx-3">Promoções</span>
                </a>
            </nav>
        </div>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700">Importar Produtos em Massa</h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">Sair</a>
                </div>
            </header>

            <main class="flex-1 p-6">
                <div class="bg-white shadow-md rounded-lg p-8">

                    <?php if (!empty($success_msg)) : ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                            <strong class="font-bold">Sucesso!</strong>
                            <span class="block sm:inline"><?php echo $success_msg; ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($error_msg)) : ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                            <strong class="font-bold">Erro!</strong>
                            <span class="block sm:inline"><?php echo $error_msg; ?></span>
                        </div>
                    <?php endif; ?>

                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Instruções</h2>
                    <div class="prose prose-sm max-w-none bg-gray-50 p-4 rounded-lg border">
                        <p>Para importar produtos, envie um arquivo no formato CSV seguindo as regras abaixo:</p>
                        <ul>
                            <li>A primeira linha do arquivo <strong>deve ser o cabeçalho</strong> (será ignorada).</li>
                            <li>A ordem das colunas deve ser exatamente:
                                <code>brand,title,sku,price,capacity,condicao,descricao</code>
                            </li>
                            <li>O campo <code>brand</code> deve ser em minúsculas (ex: <code>samsung</code>).</li>
                            <li>O campo <code>price</code> deve usar ponto como separador decimal (ex:
                                <code>149.90</code>).
                            </li>
                            <li>O campo <code>condicao</code> deve ser <code>novo</code> ou <code>retirado</code> (se
                                deixado em branco, será 'novo').</li>
                            <li>Os campos <code>capacity</code>, <code>condicao</code> e <code>descricao</code> são
                                opcionais.</li>
                        </ul>
                        <p><strong>Atenção:</strong> Se um SKU do ficheiro já existir na base de dados, o produto será
                            atualizado com as novas informações.</p>
                    </div>

                    <form action="acoes_produto.php" method="POST" enctype="multipart/form-data" class="mt-8">
                        <input type="hidden" name="acao" value="importar_csv">
                        <div>
                            <label for="csv_file" class="block text-gray-600 font-medium mb-2">Selecione o arquivo
                                CSV</label>
                            <input type="file" id="csv_file" name="csv_file" required accept=".csv, text/csv" class="w-full max-w-md text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                        </div>
                        <div class="mt-8 flex justify-start">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition">
                                <i class="fas fa-upload mr-2"></i> Iniciar Importação
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>

</html>