<?php
// Arquivo: vtn/admin/templates/sidebar.php (Com emblemas de notificação)

$currentPage = basename($_SERVER['PHP_SELF']);

// --- CÓDIGO PARA BUSCAR NOTIFICAÇÕES ---
if (isset($conn)) { // Garante que a variável de conexão exista
    // 1. Contar Pedidos Novos ('novo' ou 'chamou')
    $stmt_pedidos = $conn->query("SELECT COUNT(*) FROM pedidos WHERE status = 'novo' OR status = 'chamou'");
    $count_pedidos = $stmt_pedidos->fetchColumn();

    // 2. Contar Dúvidas Pendentes
    $stmt_duvidas = $conn->query("SELECT COUNT(*) FROM faq_perguntas WHERE status = 'pendente'");
    $count_duvidas = $stmt_duvidas->fetchColumn();

    // 3. Contar Contatos Recentes (últimas 24 horas)
    $stmt_contatos = $conn->query("SELECT COUNT(*) FROM contatos WHERE data_criacao >= NOW() - INTERVAL 1 DAY");
    $count_contatos = $stmt_contatos->fetchColumn();
} else {
    // Define como 0 se a conexão não estiver disponível para evitar erros
    $count_pedidos = 0;
    $count_duvidas = 0;
    $count_contatos = 0;
}
// --- FIM DO CÓDIGO DE NOTIFICAÇÕES ---


// Mapeamento das páginas para cada secção do menu
$visaoGeralPages = ['visao_geral.php'];
$produtosPages = ['dashboard.php', 'form_produto.php'];
$pedidosPages = ['pedidos.php', 'pedido_detalhes.php'];
$importarPages = ['importar.php'];
$adminsPages = ['admins.php', 'form_admin.php'];
$galeriaPages = ['galeria.php', 'form_imagem.php'];
$gruposImagensPages = ['grupos_imagens.php'];
$contatosPages = ['contatos.php'];
$usuariosPages = ['usuarios.php', 'usuario_detalhes.php'];
$promocoesPages = ['promocoes.php', 'form_promocao.php'];
$faqPages = ['faq.php', 'form_faq.php'];

function getMenuClass($pageGroup, $currentPage)
{
    if (in_array($currentPage, $pageGroup)) {
        return 'bg-gray-200 text-gray-800 font-semibold';
    }
    return 'text-gray-600 hover:bg-gray-200';
}
?>

<div class="w-64 bg-white shadow-md sticky top-0 h-screen">
    <div class="p-6 text-center border-b">
        <a href="<?php echo ADMIN_URL; ?>visao_geral.php">
            <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="Logo VTN" class="mx-auto h-12">
        </a>
    </div>
    <nav class="mt-4">
        <a href="<?php echo ADMIN_URL; ?>visao_geral.php" class="flex items-center justify-between py-2 px-6 <?php echo getMenuClass($visaoGeralPages, $currentPage); ?>">
            <span><i class="fas fa-chart-pie w-6 text-center"></i><span class="mx-3">Visão Geral</span></span>
        </a>
        <a href="<?php echo ADMIN_URL; ?>dashboard.php" class="flex items-center justify-between py-2 px-6 <?php echo getMenuClass($produtosPages, $currentPage); ?>">
            <span><i class="fas fa-box-open w-6 text-center"></i><span class="mx-3">Produtos</span></span>
        </a>
        <a href="<?php echo ADMIN_URL; ?>pedidos.php" class="flex items-center justify-between py-2 px-6 <?php echo getMenuClass($pedidosPages, $currentPage); ?>">
            <span><i class="fas fa-receipt w-6 text-center"></i><span class="mx-3">Pedidos</span></span>
            <?php if ($count_pedidos > 0): ?>
                <span class="text-xs font-bold text-white bg-red-500 rounded-full px-2 py-0.5"><?php echo $count_pedidos; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo ADMIN_URL; ?>faq.php" class="flex items-center justify-between py-2 px-6 <?php echo getMenuClass($faqPages, $currentPage); ?>">
            <span><i class="fas fa-question-circle w-6 text-center"></i><span class="mx-3">Dúvidas (FAQ)</span></span>
            <?php if ($count_duvidas > 0): ?>
                <span class="text-xs font-bold text-white bg-orange-500 rounded-full px-2 py-0.5"><?php echo $count_duvidas; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo ADMIN_URL; ?>importar.php" class="flex items-center justify-between py-2 px-6 <?php echo getMenuClass($importarPages, $currentPage); ?>">
            <span><i class="fas fa-file-upload w-6 text-center"></i><span class="mx-3">Importar CSV</span></span>
        </a>
        <a href="<?php echo ADMIN_URL; ?>admins.php" class="flex items-center justify-between py-2 px-6 <?php echo getMenuClass($adminsPages, $currentPage); ?>">
            <span><i class="fas fa-users-cog w-6 text-center"></i><span class="mx-3">Admins</span></span>
        </a>
        <a href="<?php echo ADMIN_URL; ?>galeria.php" class="flex items-center justify-between py-2 px-6 <?php echo getMenuClass($galeriaPages, $currentPage); ?>">
            <span><i class="fas fa-images w-6 text-center"></i><span class="mx-3">Galeria Produtos</span></span>
        </a>
        <a href="<?php echo ADMIN_URL; ?>grupos_imagens.php" class="flex items-center justify-between py-2 px-6 <?php echo getMenuClass($gruposImagensPages, $currentPage); ?>">
            <span><i class="fas fa-layer-group w-6 text-center"></i><span class="mx-3">Grupos de Imagens</span></span>
        </a>
        <a href="<?php echo ADMIN_URL; ?>contatos.php" class="flex items-center justify-between py-2 px-6 <?php echo getMenuClass($contatosPages, $currentPage); ?>">
            <span><i class="fas fa-address-book w-6 text-center"></i><span class="mx-3">Contatos</span></span>
            <?php if ($count_contatos > 0): ?>
                <span class="text-xs font-bold text-white bg-blue-500 rounded-full px-2 py-0.5"><?php echo $count_contatos; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo ADMIN_URL; ?>usuarios.php" class="flex items-center justify-between py-2 px-6 <?php echo getMenuClass($usuariosPages, $currentPage); ?>">
            <span><i class="fas fa-users w-6 text-center"></i><span class="mx-3">Usuários</span></span>
        </a>
        <a href="<?php echo ADMIN_URL; ?>promocoes.php" class="flex items-center justify-between py-2 px-6 <?php echo getMenuClass($promocoesPages, $currentPage); ?>">
            <span><i class="fas fa-tags w-6 text-center"></i><span class="mx-3">Promoções</span></span>
        </a>
    </nav>
</div>