// Arquivo: assets/js/auth.js (Completo, com link para Minhas Perguntas e Notificações)

document.addEventListener('DOMContentLoaded', () => {
    // --- ELEMENTOS DO DOM ---
    const userBtn = document.getElementById('userBtn');
    const userSidebar = document.getElementById('userSidebar');
    const closeUserSidebarBtn = document.getElementById('closeUserSidebarBtn');
    const userSidebarContent = document.getElementById('userSidebarContent');
    const overlay = document.querySelector('.overlay');
    const contactPopup = document.getElementById('contactPopup');

    // --- CRIA UMA PROMISE GLOBAL PARA O STATUS DE AUTENTICAÇÃO ---
    let resolveAuthReady;
    window.authReady = new Promise(resolve => {
        resolveAuthReady = resolve;
    });

    // --- LÓGICA DOS MENUS LATERAIS ---
    const setupSidebars = () => {
        const mainMenu = document.querySelector('.sidebar');
        const mainCloseBtn = mainMenu ? mainMenu.querySelector('.close-btn') : null;
        const mainOpenBtn = document.querySelector('.menu-btn');

        const closeAllSidebars = () => {
            if (mainMenu) mainMenu.classList.remove('active');
            if (userSidebar) userSidebar.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        };

        if (mainOpenBtn) mainOpenBtn.addEventListener('click', (e) => { e.stopPropagation(); closeAllSidebars(); if (mainMenu) mainMenu.classList.add('active'); if (overlay) overlay.classList.add('active'); document.body.style.overflow = 'hidden'; });
        if (userBtn) userBtn.addEventListener('click', (e) => { e.stopPropagation(); closeAllSidebars(); if (userSidebar) userSidebar.classList.add('active'); if (overlay) overlay.classList.add('active'); document.body.style.overflow = 'hidden'; });
        if (mainCloseBtn) mainCloseBtn.addEventListener('click', closeAllSidebars);
        if (closeUserSidebarBtn) closeUserSidebarBtn.addEventListener('click', closeAllSidebars);
        if (overlay) overlay.addEventListener('click', closeAllSidebars);
    };

    // --- FUNÇÃO PRINCIPAL QUE ATUALIZA O MENU DO USUÁRIO ---
    async function updateUserMenu() {
        try {
            const response = await fetch('session_check.php');
            const data = await response.json();

            document.body.dataset.loggedIn = data.logado;

            const headerCartLink = document.getElementById('headerCartLink');

            if (!userSidebarContent) {
                resolveAuthReady(data.logado);
                return;
            }

            const currentUrlRedirect = encodeURIComponent(window.location.href);
            if (data.logado) {
                userSidebarContent.innerHTML = `
                    <p class="user-info">Olá, <strong>${data.nome}</strong>!</p>
                    <a href="carrinho.php" class="btn-cadastro"><i class="fas fa-shopping-cart"></i> Meus Orçamentos</a>
                    <a href="minhas_perguntas.html" class="btn-cadastro"><i class="fas fa-question-circle"></i> Minhas Perguntas</a>
                    <a href="logout_usuario.php?redirect=${currentUrlRedirect}" class="btn-cadastro">Sair</a>
                `;
                if (headerCartLink) {
                    headerCartLink.href = 'carrinho.php';
                }

                // Verifica se há notificações
                if (data.unread_answers && data.unread_answers > 0) {
                    if (userBtn) userBtn.classList.add('has-notification');
                } else {
                    if (userBtn) userBtn.classList.remove('has-notification');
                }

            } else {
                const cartRedirectUrl = encodeURIComponent('carrinho.php');
                userSidebarContent.innerHTML = `<a href="login_usuario.html?redirect=${currentUrlRedirect}" class="btn-login">Fazer Login</a><a href="cadastro.html" class="btn-cadastro">Criar Conta</a>`;
                if (headerCartLink) {
                    headerCartLink.href = `login_usuario.html?redirect=${cartRedirectUrl}`;
                }
                if (contactPopup && !sessionStorage.getItem('vtn_popup_seen')) {
                    setTimeout(() => contactPopup.classList.add('active'), 3000);
                }
            }

            if (typeof updateCartIcon === 'function') {
                updateCartIcon();
            }

            resolveAuthReady(data.logado);

        } catch (error) {
            console.error('Erro ao verificar status de login:', error);
            document.body.dataset.loggedIn = 'false';
            resolveAuthReady(false);
        }
    }

    setupSidebars();
    updateUserMenu();
});