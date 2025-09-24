<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$username = htmlspecialchars($_SESSION['username']);
require_once 'config.php';

// Busca todos os contatos, dos mais recentes para os mais antigos
$stmt = $conn->query("SELECT id, nome, email, whatsapp, data_criacao FROM contatos ORDER BY data_criacao DESC");
$contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Leads de Contato - Painel</title>
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

        <?php require_once 'templates/sidebar.php'; // CAMINHO ATUALIZADO 
        ?>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700">Leads de Contato</h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="logout.php"
                        class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">Sair</a>
                </div>
            </header>
            <main class="flex-1 p-6">
                <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead class="bg-gray-50 border-b-2 border-gray-200">
                            <tr>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600 uppercase">Nome</th>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600 uppercase">E-mail</th>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600 uppercase">WhatsApp</th>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600 uppercase">Data</th>
                                <th class="p-3 text-center text-sm font-semibold text-gray-600 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($contatos as $contato): ?>
                                <tr>
                                    <td class="p-3 text-sm text-gray-700"><?php echo htmlspecialchars($contato['nome']); ?>
                                    </td>
                                    <td class="p-3 text-sm text-gray-700"><?php echo htmlspecialchars($contato['email']); ?>
                                    </td>
                                    <td class="p-3 text-sm text-gray-700">
                                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $contato['whatsapp']); ?>"
                                            target="_blank" class="text-green-600 hover:underline">
                                            <?php echo htmlspecialchars($contato['whatsapp']); ?>
                                        </a>
                                    </td>
                                    <td class="p-3 text-sm text-gray-500">
                                        <?php echo date('d/m/Y H:i', strtotime($contato['data_criacao'])); ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <a href="actions/acoes_contato.php?acao=deletar&id=<?php echo $contato['id']; ?>"
                                            class="text-red-500 hover:text-red-700 mx-2" title="Remover"
                                            onclick="return confirm('Tem certeza que deseja apagar este contato?');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($contatos)): ?>
                                <tr>
                                    <td colspan="5" class="text-center p-4 text-gray-500">Nenhum contato registrado ainda.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</body>

</html>