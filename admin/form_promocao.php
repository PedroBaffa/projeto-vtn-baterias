<?php

/**
 * @file
 * Formulário para criar uma nova promoção ou editar uma existente.
 * A página busca dados da promoção (se em modo de edição) e uma lista completa de produtos.
 */

session_start();
// Medida de segurança: Garante que apenas usuários logados possam acessar.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtém e sanitiza o nome de usuário da sessão para exibição.
$username = htmlspecialchars($_SESSION['username']);
require_once 'config.php';

// --- LÓGICA DE MODO (ADICIONAR VS. EDITAR) ---

// Inicializa variáveis padrão.
$promocao = ['id' => '', 'nome' => '', 'percentual_desconto' => '', 'data_inicio' => '', 'data_fim' => ''];
$produtos_associados = [];
$modo_edicao = isset($_GET['id']);
$id_promocao_atual = 0; // Usado para excluir a promoção atual da verificação de conflitos.

// Se a URL contiver um ID, entra no "modo de edição".
if ($modo_edicao) {
    $id_promocao_atual = (int)$_GET['id'];
    // 1. Busca os dados da promoção que está sendo editada.
    $stmt = $conn->prepare("SELECT * FROM promocoes WHERE id = :id");
    $stmt->execute([':id' => $id_promocao_atual]);
    $promocao = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Busca os IDs de todos os produtos que já estão associados a esta promoção.
    $stmt_produtos = $conn->prepare("SELECT produto_id FROM produto_promocao WHERE promocao_id = :id");
    $stmt_produtos->execute([':id' => $id_promocao_atual]);
    $produtos_associados = $stmt_produtos->fetchAll(PDO::FETCH_COLUMN, 0);
}

// --- QUERY PARA LISTAR PRODUTOS DISPONÍVEIS ---
// Esta query busca todos os produtos e, crucialmente, verifica se cada um
// já está participando de OUTRA promoção que esteja ATIVA no momento.
// Produtos em promoções conflitantes serão desabilitados no formulário.
$sql = "
    SELECT 
        p.id, p.title, p.sku, p.brand,
        -- Subquery que retorna o nome da promoção conflitante, se houver.
        (CASE 
            WHEN pr.id IS NOT NULL THEN pr.nome 
            ELSE NULL 
        END) as nome_promocao_ativa
    FROM 
        produtos p
    LEFT JOIN 
        produto_promocao pp ON p.id = pp.produto_id
    LEFT JOIN 
        promocoes pr ON pp.promocao_id = pr.id 
        AND NOW() BETWEEN pr.data_inicio AND pr.data_fim -- A promoção conflitante precisa estar ativa AGORA.
        AND pr.is_active = 1
        AND pr.id != :id_promocao_atual -- Exclui a própria promoção que estamos editando da verificação.
    GROUP BY p.id
    ORDER BY p.title ASC
";
$stmt_lista_produtos = $conn->prepare($sql);
$stmt_lista_produtos->execute([':id_promocao_atual' => $id_promocao_atual]);
$lista_produtos = $stmt_lista_produtos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title><?php echo $modo_edicao ? 'Editar' : 'Criar'; ?> Promoção - Painel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .produto-item.hidden {
            display: none;
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
                <h1 class="text-2xl font-semibold text-gray-700"><?php echo $modo_edicao ? 'Editar' : 'Criar Nova'; ?> Promoção</h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">Sair</a>
                </div>
            </header>
            <main class="flex-1 p-6">
                <div class="bg-white shadow-md rounded-lg p-8 max-w-4xl mx-auto">
                    <form action="acoes_promocao.php" method="POST">
                        <input type="hidden" name="acao" value="<?php echo $modo_edicao ? 'editar' : 'criar'; ?>">
                        <?php if ($modo_edicao): ?>
                            <input type="hidden" name="id" value="<?php echo $promocao['id']; ?>">
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="nome" class="block text-gray-600 font-medium mb-2">Nome da Promoção</label>
                                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($promocao['nome']); ?>" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                            <div>
                                <label for="percentual_desconto" class="block text-gray-600 font-medium mb-2">Desconto (%)</label>
                                <input type="number" step="0.01" id="percentual_desconto" name="percentual_desconto" value="<?php echo htmlspecialchars($promocao['percentual_desconto']); ?>" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ex: 15.50">
                            </div>
                            <div>
                                <label for="data_inicio" class="block text-gray-600 font-medium mb-2">Data de Início</label>
                                <input type="datetime-local" id="data_inicio" name="data_inicio" value="<?php echo $promocao['data_inicio'] ? date('Y-m-d\TH:i', strtotime($promocao['data_inicio'])) : ''; ?>" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                            <div>
                                <label for="data_fim" class="block text-gray-600 font-medium mb-2">Data de Fim</label>
                                <input type="datetime-local" id="data_fim" name="data_fim" value="<?php echo $promocao['data_fim'] ? date('Y-m-d\TH:i', strtotime($promocao['data_fim'])) : ''; ?>" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                        </div>

                        <div class="mt-6">
                            <label class="block text-gray-600 font-medium mb-2">Aplicar aos Produtos</label>
                            <div class="bg-gray-50 p-4 rounded-lg border mb-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <input type="text" id="search-filter" placeholder="Filtrar por nome ou SKU..." class="w-full px-4 py-2 border rounded-lg">
                                    </div>
                                    <div>
                                        <select id="brand-filter" class="w-full px-4 py-2 border rounded-lg">
                                            <option value="all">Todas as Marcas</option>
                                            <?php $marcas = ['samsung', 'apple', 'xiaomi', 'lg', 'motorola', 'huawei', 'asus', 'lenovo', 'nokia', 'positivo', 'multilaser', 'philco', 'infinix'];
                                            foreach ($marcas as $marca): ?>
                                                <option value="<?php echo $marca; ?>"><?php echo ucfirst($marca); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center">
                                    <input type="checkbox" id="select-all-visible" class="h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                                    <label for="select-all-visible" class="ml-2 text-sm font-medium text-gray-700">Selecionar/Desselecionar Todos Visíveis</label>
                                </div>
                            </div>

                            <div id="product-list-container" class="h-64 border rounded-lg p-2 overflow-y-auto">
                                <?php foreach ($lista_produtos as $produto):
                                    // Verifica se o produto está em outra promoção para desabilitá-lo.
                                    $is_disabled = !empty($produto['nome_promocao_ativa']);
                                ?>
                                    <div class="produto-item flex items-center p-1 hover:bg-gray-100 rounded <?php echo $is_disabled ? 'opacity-50' : ''; ?>" data-brand="<?php echo htmlspecialchars($produto['brand']); ?>" data-text="<?php echo htmlspecialchars(strtolower($produto['title'] . $produto['sku'])); ?>">
                                        <input
                                            type="checkbox"
                                            name="produtos[]"
                                            id="produto_<?php echo $produto['id']; ?>"
                                            value="<?php echo $produto['id']; ?>"
                                            <?php echo in_array($produto['id'], $produtos_associados) ? 'checked' : ''; // Marca os produtos já associados 
                                            ?>
                                            <?php echo $is_disabled ? 'disabled' : ''; // Desabilita o checkbox se necessário 
                                            ?>
                                            class="h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500 product-checkbox">

                                        <label for="produto_<?php echo $produto['id']; ?>" class="ml-2 text-sm text-gray-700 <?php echo $is_disabled ? 'cursor-not-allowed' : 'cursor-pointer'; ?>">
                                            <?php echo htmlspecialchars($produto['title']); ?> (SKU: <?php echo htmlspecialchars($produto['sku']); ?>)
                                        </label>

                                        <?php if ($is_disabled): ?>
                                            <small class="text-red-500 ml-auto text-xs font-semibold whitespace-nowrap">
                                                (Em promoção: <?php echo htmlspecialchars($produto['nome_promocao_ativa']); ?>)
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <a href="promocoes.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-6 rounded-lg mr-4">Cancelar</a>
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg">Salvar Promoção</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-filter');
            const brandInput = document.getElementById('brand-filter');
            const productItems = document.querySelectorAll('.produto-item');
            const selectAllCheckbox = document.getElementById('select-all-visible');

            // Função para filtrar a lista de produtos com base nos inputs de busca e marca.
            function filterProducts() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedBrand = brandInput.value;

                productItems.forEach(item => {
                    const itemText = item.dataset.text; // Atributo data-* com texto concatenado para busca.
                    const itemBrand = item.dataset.brand;
                    const matchesSearch = itemText.includes(searchTerm);
                    const matchesBrand = (selectedBrand === 'all' || itemBrand === selectedBrand);

                    // Mostra ou esconde o item com base nos filtros.
                    item.classList.toggle('hidden', !(matchesSearch && matchesBrand));
                });
            }

            // Função para marcar ou desmarcar todos os produtos visíveis.
            function toggleSelectAll() {
                const isChecked = selectAllCheckbox.checked;
                productItems.forEach(item => {
                    // Aplica a ação apenas aos itens que não estão escondidos pelo filtro.
                    if (!item.classList.contains('hidden')) {
                        const checkbox = item.querySelector('.product-checkbox');
                        // Garante que não vai alterar checkboxes desabilitados.
                        if (checkbox && !checkbox.disabled) {
                            checkbox.checked = isChecked;
                        }
                    }
                });
            }

            // Adiciona os event listeners aos elementos de filtro.
            searchInput.addEventListener('input', filterProducts);
            brandInput.addEventListener('change', filterProducts);
            selectAllCheckbox.addEventListener('change', toggleSelectAll);
        });
    </script>
</body>

</html>