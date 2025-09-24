<?php
session_start();

// Se o usuário não estiver logado, redireciona para a página de login
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login_usuario.html');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Carrinho - VTN Baterias</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="shortcut icon" href="assets/img/favicon.png" type="image/png">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/carrinho.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
</head>

<body>
    <header class="meu-header">
        <div class="header-side">
            <button class="menu-btn">☰</button>
            <button class="search-btn" id="searchBtn" aria-label="Pesquisar Produtos">
                <i class="fas fa-search"></i>
        </div>
        <a href="index.html" class="logo"><img src="assets/img/logo.png" alt="Logo VTN" class="logo-img"></a>
        <div class="header-side">
            <a href="#" id="headerCartLink" class="header-cart-btn">
                <i class="fas fa-shopping-cart"></i>
                <span>Carrinho (0)</span>
            </a>
            <button class="user-btn" id="userBtn" aria-label="Área do Usuário">
                <i class="fas fa-user"></i>
            </button>
        </div>

        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Menu</h3><button class="close-btn">×</button>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.html"><i class="fas fa-home"></i> Início</a></li>
                <li><a href="catalogo_produtos.php"><i class="fas fa-battery-full"></i> Catálogo</a></li>
                <li><a href="https://wa.me/5513997979637" target="_blank"><i class="fab fa-whatsapp"></i> Nosso
                        Whatsapp</a></li>
            </ul>
        </div>

        <div class="overlay"></div>

        <div class="user-sidebar" id="userSidebar">
            <div class="sidebar-header">
                <h3>Minha Conta</h3>
                <button class="close-btn" id="closeUserSidebarBtn">×</button>
            </div>
            <div id="userSidebarContent" class="user-sidebar-content"></div>
        </div>
    </header>

    <div class="container-carrinho">
        <h1>Meu Carrinho de Orçamento</h1>
        <div id="cartItemsContainer">
        </div>
        <div class="cart-summary" id="cartSummary">
        </div>

        <div id="emptyCartMessage" class="empty-cart-message" style="display: none;">
            <i class="fas fa-shopping-cart"></i>
            <p>Seu carrinho está vazio.</p>
            <a href="catalogo_produtos.php" class="btn-primary">Ver Produtos</a>
        </div>
    </div>

    <script src="assets/js/index.js"></script>
    <script src="assets/js/auth.js"></script>
    <script src="assets/js/carrinho.js"></script>

    <section class="whatsapp-contact-section">
        <div class="whatsapp-contact-content">
            <h2>Fale Conosco via WhatsApp</h2>
            <p>Envie-nos uma mensagem rápida e entraremos em contato!</p>
            <div class="whatsapp-input-group">
                <input type="text" id="whatsappMessageInput" class="whatsapp-message-input"
                    placeholder="Digite sua mensagem aqui...">
                <a href="https://wa.me/5513997979637" id="whatsappSectionBtn" class="whatsapp-section-btn"
                    target="_blank">
                    <i class="fab fa-whatsapp"></i> Enviar Mensagem
                </a>
            </div>
        </div>
    </section>
    </main>

    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-column">
                <img src="assets/img/logo.png" alt="Logo Grupo VTN" class="footer-logo">
                <p>Qualidade e confiança em baterias para o seu dia a dia, há mais de 13 anos no mercado.</p>
            </div>

            <div class="footer-column">
                <h4>Informações de Contato</h4>
                <p><i class="fas fa-building"></i> Robin Hood Importacao, Exportacao e Comercio LTDA</p>
                <p><i class="far fa-id-card"></i> CNPJ: 17.157.023/0001-07</p>
                <p><i class="fas fa-map-marker-alt"></i> Rua Comendador Assad Abdalla, 22 - São Paulo, SP</p>
                <p><i class="fab fa-whatsapp"></i> <a href="https://wa.me/5513997979637" target="_blank">+55 (13)
                        99797-9637</a></p>
            </div>

            <div class="footer-column">
                <h4>Navegação</h4>
                <ul>
                    <li><a href="index.html">Início</a></li>
                    <li><a href="catalogo_produtos.php">Catálogo Completo</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 Grupo VTN. Todos os direitos reservados.</p>
            <p>DESENVOLVIDO POR PEDRO BAFFA</p>
        </div>
    </footer>

    <div id="confirmDeleteModal" class="custom-modal-overlay">
        <div class="custom-modal-content">
            <h3 class="custom-modal-title"><i class="fas fa-exclamation-triangle"></i> Confirmar Remoção</h3>
            <p class="custom-modal-text">Tem a certeza de que deseja remover este item do seu orçamento?</p>
            <div class="custom-modal-actions">
                <button id="cancelDeleteBtn" class="modal-btn secondary">Cancelar</button>
                <button id="confirmDeleteBtn" class="modal-btn danger">Remover</button>
            </div>
        </div>
    </div>

    <div id="searchModal" class="search-modal">
        <div class="search-modal-content">
            <div class="search-modal-header">
                <h3>Pesquisar Produtos</h3>
                <button id="closeSearchBtn" class="close-search-btn">&times;</button>
            </div>
            <div class="search-input-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="modalSearchInput" class="search-input"
                    placeholder="Buscar por marca, modelo ou SKU...">
            </div>
            <div id="searchResults" class="search-results">
            </div>
        </div>
    </div>

</body>

</html>