<?php

/**
 * @file
 * Página de Visão Geral (Dashboard) do painel de administração.
 * Exibe um resumo das principais métricas do site, como total de produtos,
 * contatos recentes e promoções ativas.
 */

session_start();
// Medida de segurança: Garante que apenas usuários logados possam acessar.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtém e sanitiza o nome de usuário da sessão para exibição.
$username = htmlspecialchars($_SESSION['username']);
// Inclui a conexão com o banco de dados.
require_once 'config.php';

// --- BUSCA DAS ESTATÍSTICAS E PRÉ-VISUALIZAÇÕES PARA OS CARDS ---

// Card 1: Total de Produtos
// Busca os 3 produtos mais recentes para a pré-visualização.
$stmt_total_produtos = $conn->query("SELECT id, title, sku FROM produtos ORDER BY id DESC LIMIT 3");
$produtos_recentes_preview = $stmt_total_produtos->fetchAll(PDO::FETCH_ASSOC);
// Conta o número total de produtos na tabela.
$total_produtos = $conn->query("SELECT COUNT(*) FROM produtos")->fetchColumn();

// Card 2: Contatos (Leads)
// Busca os 3 contatos mais recentes para a pré-visualização.
$stmt_contatos = $conn->query("SELECT id, nome, whatsapp FROM contatos ORDER BY data_criacao DESC LIMIT 3");
$contatos_preview = $stmt_contatos->fetchAll(PDO::FETCH_ASSOC);
// Conta o total de contatos recebidos.
$total_contatos = $conn->query("SELECT COUNT(*) FROM contatos")->fetchColumn();

// Card 3: Produtos Sem Imagem (Informação de Manutenção)
// Query útil que encontra produtos que não têm nenhuma imagem associada.
$stmt_sem_imagem = $conn->query(
    "SELECT p.id, p.title, p.sku FROM produtos p 
     LEFT JOIN produto_imagens pi ON p.id = pi.produto_id 
     WHERE pi.id IS NULL LIMIT 3"
);
$produtos_sem_imagem_preview = $stmt_sem_imagem->fetchAll(PDO::FETCH_ASSOC);
$total_sem_imagem = $conn->query("SELECT COUNT(p.id) FROM produtos p LEFT JOIN produto_imagens pi ON p.id = pi.produto_id WHERE pi.id IS NULL")->fetchColumn();

// Card 4: Total de Administradores
// Busca os 3 administradores mais recentes.
$stmt_admins = $conn->query("SELECT id, usuario FROM administradores ORDER BY id DESC LIMIT 3");
$admins_preview = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);
// Conta o total de administradores.
$total_admins = $conn->query("SELECT COUNT(*) FROM administradores")->fetchColumn();

// Card 5: Promoções Ativas
// Busca as 3 promoções mais recentes que estão atualmente ativas.
$stmt_promocoes = $conn->query("SELECT id, nome FROM promocoes WHERE is_active = 1 AND NOW() BETWEEN data_inicio AND data_fim ORDER BY data_criacao DESC LIMIT 3");
$promocoes_ativas_preview = $stmt_promocoes->fetchAll(PDO::FETCH_ASSOC);
// Conta o total de promoções ativas.
$total_promocoes_ativas = $conn->query("SELECT COUNT(*) FROM promocoes WHERE is_active = 1 AND NOW() BETWEEN data_inicio AND data_fim")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Visão Geral - Painel Administrativo</title>
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
                <a href="visao_geral.php" class="flex items-center py-2 px-6 bg-gray-200 text-gray-800 font-semibold"><i class="fas fa-chart-pie w-6 text-center"></i><span class="mx-3">Visão Geral</span></a>
                <a href="dashboard.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-box-open w-6 text-center"></i><span class="mx-3">Produtos</span></a>
                <a href="importar.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-file-upload w-6 text-center"></i><span class="mx-3">Importar CSV</span></a>
                <a href="admins.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-users-cog w-6 text-center"></i><span class="mx-3">Admins</span></a>
                <a href="galeria.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-images w-6 text-center"></i><span class="mx-3">Galeria</span></a>
                <a href="contatos.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-address-book w-6 text-center"></i><span class="mx-3">Contatos</span></a>
                <a href="usuarios.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-users w-6 text-center"></i><span class="mx-3">Usuários</span></a>
                <a href="promocoes.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-tags w-6 text-center"></i><span class="mx-3">Promoções</span></a>
            </nav>
        </div>
        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700">Visão Geral</h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">Sair</a>
                </div>
            </header>
            <main class="flex-1 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-700">Total de Produtos</h3>
                            <span class="text-2xl font-bold text-blue-500"><?php echo $total_produtos; ?></span>
                        </div>
                        <div class="space-y-3">
                            <p class="text-xs text-gray-400 font-semibold uppercase">Últimos Adicionados</p>
                            <?php foreach ($produtos_recentes_preview as $produto): ?>
                                <a href="form_produto.php?acao=editar&id=<?php echo $produto['id']; ?>" class="block hover:bg-gray-50 p-2 rounded-md">
                                    <p class="font-medium text-gray-800 truncate"><?php echo htmlspecialchars($produto['title']); ?></p>
                                    <p class="text-xs text-gray-400">SKU: <?php echo htmlspecialchars($produto['sku']); ?></p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="dashboard.php" class="text-sm font-semibold text-blue-600 hover:underline">Ver Todos &rarr;</a>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-700">Promoções Ativas</h3>
                            <span class="text-2xl font-bold text-green-500"><?php echo $total_promocoes_ativas; ?></span>
                        </div>
                        <div class="space-y-3">
                            <p class="text-xs text-gray-400 font-semibold uppercase">Promoções em Vigor</p>
                            <?php if (empty($promocoes_ativas_preview)): ?>
                                <p class="text-sm text-gray-500 italic">Nenhuma promoção ativa no momento.</p>
                            <?php else: ?>
                                <?php foreach ($promocoes_ativas_preview as $promo): ?>
                                    <a href="form_promocao.php?id=<?php echo $promo['id']; ?>" class="block hover:bg-gray-50 p-2 rounded-md">
                                        <p class="font-medium text-gray-800 truncate"><i class="fas fa-tags mr-2 text-gray-400"></i><?php echo htmlspecialchars($promo['nome']); ?></p>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="promocoes.php" class="text-sm font-semibold text-blue-600 hover:underline">Ver Todas &rarr;</a>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-700">Contatos (Leads)</h3>
                            <span class="text-2xl font-bold text-purple-500"><?php echo $total_contatos; ?></span>
                        </div>
                        <div class="space-y-3">
                            <p class="text-xs text-gray-400 font-semibold uppercase">Últimos Registrados</p>
                            <?php if (empty($contatos_preview)): ?>
                                <p class="text-sm text-gray-500 italic">Nenhum contato ainda.</p>
                            <?php else: ?>
                                <?php foreach ($contatos_preview as $contato): ?>
                                    <div class="block hover:bg-gray-50 p-2 rounded-md">
                                        <p class="font-medium text-gray-800 truncate"><i class="fas fa-user mr-2 text-gray-400"></i><?php echo htmlspecialchars($contato['nome']); ?></p>
                                        <p class="text-xs text-gray-400 ml-6"><?php echo htmlspecialchars($contato['whatsapp']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="contatos.php" class="text-sm font-semibold text-blue-600 hover:underline">Ver Todos &rarr;</a>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-700">Administradores</h3>
                            <span class="text-2xl font-bold text-gray-800"><?php echo $total_admins; ?></span>
                        </div>
                        <div class="space-y-3">
                            <p class="text-xs text-gray-400 font-semibold uppercase">Usuários</p>
                            <?php foreach ($admins_preview as $admin): ?>
                                <a href="form_admin.php?acao=editar&id=<?php echo $admin['id']; ?>" class="block hover:bg-gray-50 p-2 rounded-md">
                                    <p class="font-medium text-gray-800"><i class="fas fa-user mr-2 text-gray-400"></i><?php echo htmlspecialchars($admin['usuario']); ?></p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="admins.php" class="text-sm font-semibold text-blue-600 hover:underline">Ver Todos &rarr;</a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>