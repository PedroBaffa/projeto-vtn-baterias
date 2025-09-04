<?php

/**
 * @file
 * Página do painel de administração para visualizar e gerenciar as promoções.
 * Exibe uma lista de todas as promoções e o status de cada uma (ativa, agendada, expirada).
 */

session_start();
// Medida de segurança: Garante que apenas usuários logados possam acessar.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtém e sanitiza o nome de usuário da sessão para exibição.
$username = htmlspecialchars($_SESSION['username']);
// Inclui o arquivo de configuração para a conexão com o banco de dados.
require_once 'config.php';

// --- BUSCA DAS PROMOÇÕES ---

// Query que seleciona todas as promoções e calcula um campo 'status' dinamicamente.
$stmt = $conn->query("SELECT *,
    -- A expressão CASE é usada para determinar o status da promoção com base nas datas.
    CASE
        -- Se a data atual estiver entre o início e o fim da promoção, ela está 'ativa'.
        WHEN NOW() BETWEEN data_inicio AND data_fim AND is_active = 1 THEN 'ativa'
        -- Se a data atual for anterior à data de início, ela está 'agendada'.
        WHEN NOW() < data_inicio AND is_active = 1 THEN 'agendada'
        -- Caso contrário, é considerada 'expirada'.
        ELSE 'expirada'
    END as status
    FROM promocoes ORDER BY data_criacao DESC");
$promocoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gerenciar Promoções - Painel</title>
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
            <div class="p-6 text-center border-b"><a href="visao_geral.php"><img src="../assets/img/logo.png" alt="Logo VTN" class="mx-auto h-12"></a></div>
            <nav class="mt-4">
                <a href="visao_geral.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-chart-pie w-6 text-center"></i><span class="mx-3">Visão Geral</span></a>
                <a href="dashboard.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-box-open w-6 text-center"></i><span class="mx-3">Produtos</span></a>
                <a href="importar.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-file-upload w-6 text-center"></i><span class="mx-3">Importar CSV</span></a>
                <a href="admins.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-users-cog w-6 text-center"></i><span class="mx-3">Admins</span></a>
                <a href="galeria.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-images w-6 text-center"></i><span class="mx-3">Galeria</span></a>
                <a href="contatos.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-address-book w-6 text-center"></i><span class="mx-3">Contatos</span></a>
                <a href="usuarios.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-users w-6 text-center"></i><span class="mx-3">Usuários</span></a>
                <a href="promocoes.php" class="flex items-center py-2 px-6 bg-gray-200 text-gray-800 font-semibold"><i class="fas fa-tags w-6 text-center"></i><span class="mx-3">Promoções</span></a>
            </nav>
        </div>
        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700">Gerenciar Promoções</h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">Sair</a>
                </div>
            </header>
            <main class="flex-1 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Lista de Promoções</h2>
                    <a href="form_promocao.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">
                        <i class="fas fa-plus mr-2"></i> Criar Nova Promoção
                    </a>
                </div>
                <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead class="bg-gray-50 border-b-2 border-gray-200">
                            <tr>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600 uppercase">Nome</th>
                                <th class="p-3 text-center text-sm font-semibold text-gray-600 uppercase">Desconto</th>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600 uppercase">Período de Validade</th>
                                <th class="p-3 text-center text-sm font-semibold text-gray-600 uppercase">Status</th>
                                <th class="p-3 text-center text-sm font-semibold text-gray-600 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($promocoes)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-10 text-gray-500">Nenhuma promoção criada ainda.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($promocoes as $promo): ?>
                                    <tr>
                                        <td class="p-3 text-sm text-gray-700"><?php echo htmlspecialchars($promo['nome']); ?></td>
                                        <td class="p-3 text-center text-sm text-red-500 font-bold"><?php echo htmlspecialchars(number_format($promo['percentual_desconto'], 2, ',', '.')); ?>%</td>
                                        <td class="p-3 text-sm text-gray-600">
                                            <?php echo date('d/m/Y H:i', strtotime($promo['data_inicio'])); ?> - <?php echo date('d/m/Y H:i', strtotime($promo['data_fim'])); ?>
                                        </td>
                                        <td class="p-3 text-center text-sm">
                                            <?php if ($promo['status'] == 'ativa'): ?>
                                                <span class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full">Ativa</span>
                                            <?php elseif ($promo['status'] == 'agendada'): ?>
                                                <span class="px-2 py-1 font-semibold leading-tight text-blue-700 bg-blue-100 rounded-full">Agendada</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 font-semibold leading-tight text-gray-700 bg-gray-100 rounded-full">Expirada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3 text-center">
                                            <a href="form_promocao.php?id=<?php echo $promo['id']; ?>" class="text-blue-500 hover:text-blue-700 mx-2" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                                            <a href="acoes_promocao.php?acao=deletar&id=<?php echo $promo['id']; ?>" class="text-red-500 hover:text-red-700 mx-2" title="Remover" onclick="return confirm('Tem certeza? Isso removerá a promoção de todos os produtos associados.');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</body>

</html>