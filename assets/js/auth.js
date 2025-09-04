/**
 * @file
 * assets/js/auth.js
 * Gerencia a autenticação do usuário no front-end.
 * Verifica se o usuário está logado, atualiza a interface (menus, links)
 * e controla a exibição dos menus laterais (principal e de usuário).
 */

document.addEventListener('DOMContentLoaded', () => {
    // --- ELEMENTOS DO DOM ---
    const userBtn = document.getElementById('userBtn');
    const userSidebar = document.getElementById('userSidebar');
    const closeUserSidebarBtn = document.getElementById('closeUserSidebarBtn');
    const userSidebarContent = document.getElementById('userSidebarContent');
    const overlay = document.querySelector('.overlay');
    const contactPopup = document.getElementById('contactPopup');

    // --- PROMISE GLOBAL DE AUTENTICAÇÃO ---
    // Cria uma Promise global chamada 'authReady'. Outros scripts (como carrinho.js)
    // podem "esperar" por esta promise antes de executar lógicas que dependem
    // de saber se o usuário está logado ou não. É uma forma elegante de sincronizar scripts.
    let resolveAuthReady;
    window.authReady = new Promise(resolve => {
        resolveAuthReady = resolve;
    });

    // --- LÓGICA DOS MENUS LATERAIS ---
    const setupSidebars = () => {
        const mainMenu = document.querySelector('.sidebar');
        const mainCloseBtn = mainMenu ? mainMenu.querySelector('.close-btn') : null;
        const mainOpenBtn = document.querySelector('.menu-btn');

        // Função central para fechar todos os menus abertos e o overlay.
        const closeAllSidebars = () => {
            if (mainMenu) mainMenu.classList.remove('active');
            if (userSidebar) userSidebar.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = 'auto'; // Devolve a rolagem para a página.
        };

        // Adiciona os eventos de clique para abrir e fechar os menus.
        if (mainOpenBtn) mainOpenBtn.addEventListener('click', (e) => { e.stopPropagation(); closeAllSidebars(); if (mainMenu) mainMenu.classList.add('active'); if (overlay) overlay.classList.add('active'); document.body.style.overflow = 'hidden'; });
        if (userBtn) userBtn.addEventListener('click', (e) => { e.stopPropagation(); closeAllSidebars(); if (userSidebar) userSidebar.classList.add('active'); if (overlay) overlay.classList.add('active'); document.body.style.overflow = 'hidden'; });
        if (mainCloseBtn) mainCloseBtn.addEventListener('click', closeAllSidebars);
        if (closeUserSidebarBtn) closeUserSidebarBtn.addEventListener('click', closeAllSidebars);
        if (overlay) overlay.addEventListener('click', closeAllSidebars);
    };

    // --- FUNÇÃO PRINCIPAL QUE ATUALIZA O MENU DO USUÁRIO ---
    async function updateUserMenu() {
        try {
            // Faz uma chamada à API de back-end para verificar o status da sessão.
            const response = await fetch('session_check.php');
            const data = await response.json();

            // Adiciona um atributo 'data-logged-in' ao body para que o CSS possa usar.
            document.body.dataset.loggedIn = data.logado;

            const headerCartLink = document.getElementById('headerCartLink');

            if (!userSidebarContent) {
                resolveAuthReady(data.logado); // Resolve a promise mesmo que o sidebar não exista.
                return;
            }

            // Pega a URL atual para usá-la em redirecionamentos de login/logout.
            const currentUrlRedirect = encodeURIComponent(window.location.href);

            if (data.logado) {
                // Se o usuário ESTÁ LOGADO:
                // Atualiza o menu do usuário com saudação e link de "Sair".
                userSidebarContent.innerHTML = `<p class="user-info">Olá, <strong>${data.nome}</strong>!</p><a href="carrinho.php" class="btn-cadastro"><i class="fas fa-shopping-cart"></i> Meus Orçamentos</a><a href="logout_usuario.php?redirect=${currentUrlRedirect}" class="btn-cadastro">Sair</a>`;
                // O link do carrinho no header aponta diretamente para a página do carrinho.
                if (headerCartLink) {
                    headerCartLink.href = 'carrinho.php';
                }
            } else {
                // Se o usuário NÃO ESTÁ LOGADO:
                // Atualiza o menu com os botões "Fazer Login" and "Criar Conta".
                const cartRedirectUrl = encodeURIComponent('carrinho.php');
                userSidebarContent.innerHTML = `<a href="login_usuario.html?redirect=${currentUrlRedirect}" class="btn-login">Fazer Login</a><a href="cadastro.html" class="btn-cadastro">Criar Conta</a>`;
                // O link do carrinho no header aponta para a página de login, com um redirecionamento para o carrinho após o sucesso.
                if (headerCartLink) {
                    headerCartLink.href = `login_usuario.html?redirect=${cartRedirectUrl}`;
                }
                // Mostra o popup de contato se ainda não foi visto nesta sessão do navegador.
                if (contactPopup && !sessionStorage.getItem('vtn_popup_seen')) {
                    setTimeout(() => contactPopup.classList.add('active'), 3000);
                }
            }

            // Chama a função global (de carrinho.js) para atualizar o contador de itens no ícone do carrinho.
            if (typeof updateCartIcon === 'function') {
                updateCartIcon();
            }

            // Resolve a promise, informando a outros scripts que a verificação de auth foi concluída.
            resolveAuthReady(data.logado);

        } catch (error) {
            console.error('Erro ao verificar status de login:', error);
            document.body.dataset.loggedIn = 'false';
            resolveAuthReady(false); // Resolve a promise como 'false' em caso de erro.
        }
    }

    // Inicializa as funcionalidades ao carregar a página.
    setupSidebars();
    updateUserMenu();
});