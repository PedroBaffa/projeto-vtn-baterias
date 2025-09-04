<?php

/**
 * @file
 * Página da Galeria de Imagens do painel de administração.
 * Exibe todas as imagens de produtos e fornece uma interface para upload,
 * exclusão individual e exclusão em massa. A lógica interativa é
 * controlada pelo 'assets/js/galeria.js'.
 */

session_start();
// Medida de segurança: Garante que apenas usuários logados possam acessar esta página.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtém e sanitiza o nome de usuário da sessão para exibição no cabeçalho.
$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Galeria de Imagens - Painel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .image-card {
            position: relative;
            overflow: hidden;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            cursor: pointer;
            border: 2px solid transparent;
        }

        /* Estilo aplicado via JavaScript quando uma imagem é selecionada */
        .image-card.selected {
            border-color: #2563eb;
            /* Azul para indicar seleção */
        }

        /* Overlay com botões de ação que aparece ao passar o mouse */
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .image-card:hover .image-overlay {
            opacity: 1;
        }

        /* Estilo para a área de upload com drag-and-drop */
        .upload-area {
            border: 2px dashed #ccc;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <div class="w-64 bg-white shadow-md sticky top-0 h-screen">
            <div class="p-6 text-center border-b"><a href="visao_geral.php"><img src="../assets/img/logo.png" alt="Logo VTN" class="mx-auto h-12"></a></div>
            <nav class="mt-4">
                <a href="visao_geral.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-chart-pie w-6 text-center"></i><span class="mx-3">Visão Geral</span></a>
                <a href="dashboard.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-box-open w-6 text-center"></i><span class="mx-3">Produtos</span></a>
                <a href="importar.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-file-upload w-6 text-center"></i><span class="mx-3">Importar CSV</span></a>
                <a href="admins.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-users-cog w-6 text-center"></i><span class="mx-3">Admins</span></a>
                <a href="galeria.php" class="flex items-center py-2 px-6 bg-gray-200 text-gray-800 font-semibold"><i class="fas fa-images w-6 text-center"></i><span class="mx-3">Galeria</span></a>
                <a href="contatos.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-address-book w-6 text-center"></i><span class="mx-3">Contatos</span></a>
                <a href="usuarios.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-users w-6 text-center"></i><span class="mx-3">Usuários</span></a>
                <a href="promocoes.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-tags w-6 text-center"></i><span class="mx-3">Promoções</span></a>
            </nav>
        </div>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700">Galeria de Imagens</h1>
                <div class="flex items-center"><span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span><a href="logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">Sair</a></div>
            </header>

            <main class="flex-1 p-6">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold">Imagens Enviadas</h2>
                        <div id="gallery-actions">
                            <button id="select-mode-btn" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg"><i class="fas fa-check-square mr-2"></i>Selecionar</button>
                        </div>

                        <div id="selection-actions" class="hidden flex items-center gap-4">
                            <button id="delete-selected-btn" class="bg-red-600 text-white font-bold py-2 px-4 rounded-lg"><i class="fas fa-trash-alt mr-2"></i>Apagar Selecionadas</button>
                            <button id="cancel-selection-btn" class="bg-gray-500 text-white font-bold py-2 px-4 rounded-lg">Cancelar</button>
                        </div>
                    </div>

                    <div id="galleryGrid" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../assets/js/galeria.js"></script>
</body>

</html>