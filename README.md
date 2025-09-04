# Grupo VTN Baterias - E-commerce & Painel de Gerenciamento

Este é um projeto completo de e-commerce para a "VTN Baterias", desenvolvido como parte do meu portfólio. O sistema inclui uma área pública para clientes (catálogo, carrinho, login) e um painel de administração robusto para gerenciamento de produtos, usuários, promoções e mais.

## ✨ Funcionalidades

### Área do Cliente (Front-end)
-   Catálogo de produtos com filtros e busca.
-   Página de detalhes do produto.
-   Sistema de login e cadastro de usuários.
-   Carrinho de orçamento com finalização via WhatsApp.
-   Cálculo de frete em tempo real (integração com Melhor Envio).

### Painel de Administração (Back-end)
-   Dashboard com visão geral das métricas do site.
-   CRUD completo de Produtos (Adicionar, Editar, Apagar).
-   Importação de produtos em massa via CSV.
-   Gerenciamento de imagens com galeria e upload.
-   Criação e gerenciamento de promoções com aplicação de descontos.
-   Visualização de leads (contatos), usuários e administradores.

## 🛠️ Tecnologias Utilizadas

-   **Front-end:** HTML5, CSS3, JavaScript (ES6+), TailwindCSS (via CDN).
-   **Back-end:** PHP 7.2+.
-   **Banco de Dados:** MariaDB / MySQL.
-   **Bibliotecas JS:** FontAwesome, IMask.js.

## 🚀 Como Executar o Projeto

Siga os passos abaixo para configurar e rodar o projeto em um ambiente local (como XAMPP ou WAMP).

1.  **Clone o Repositório**
    ```bash
    git clone [https://github.com/PedroBaffa/projeto-vtn-ecomerce.git](https://github.com/seu-usuario/nome-do-repositorio.git)
    cd nome-do-repositorio
    ```

2.  **Configure o Banco de Dados**
    -   Crie um novo banco de dados no seu servidor MySQL/MariaDB (ex: `grupo_vtn_db`).
    -   Importe o arquivo `sqls/grupo_vtn_db.sql` para criar todas as tabelas e estruturas necessárias.

3.  **Configure as Credenciais**
    -   Na pasta `admin/`, renomeie o arquivo `config.example.php` para `config.php`.
    -   Abra o `config.php` e preencha com as suas credenciais do banco de dados.
    -   (Opcional) Adicione seu token da API do Melhor Envio no arquivo `frete_api.php`.

4.  **Execute o Projeto**
    -   Coloque a pasta do projeto no diretório do seu servidor web (ex: `htdocs` no XAMPP).
    -   Acesse `http://localhost/grupo_vtn/` no seu navegador para ver o site.
    -   Acesse `http://localhost/grupo_vtn/admin/` para o painel administrativo.
