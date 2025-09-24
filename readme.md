<p align="center">
  <img src="assets/img/logo.png" alt="Logo Grupo VTN" width="300"/>
</p>

<h1 align="center">Grupo VTN Baterias - Plataforma de E-commerce</h1>

<p align="center">
  <strong>Uma solução completa de e-commerce para venda de baterias, com catálogo dinâmico e um poderoso painel administrativo para gerenciamento total da loja.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript">
  <img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS">
</p>

---

## 📋 Sobre o Projeto

O **Grupo VTN Baterias** é uma plataforma web robusta projetada para a comercialização de baterias de celular, tanto no varejo quanto no atacado. O sistema conta com uma área para clientes com catálogo, busca e carrinho de orçamentos, e um painel administrativo completo para gerenciar todos os aspectos do negócio, desde produtos e estoque até pedidos e promoções.

## ✨ Funcionalidades Principais

### 👤 Área do Cliente (Front-end)

* **Catálogo Dinâmico:** Produtos carregados diretamente do banco de dados, com filtros dinâmicos por marca que se atualizam automaticamente.
* **Busca Inteligente:** Pesquisa de produtos em tempo real por nome, marca ou SKU.
* **Paginação:** Navegação otimizada para catálogos com grande quantidade de produtos.
* **Sistema de Login/Cadastro:** Área de cliente segura para acompanhamento de atividades.
* **Carrinho de Orçamento:** Funcionalidade de carrinho de compras que gera um pedido de orçamento detalhado para finalização via WhatsApp.
* **Cálculo de Frete:** Integração com a API do Melhor Envio para cálculo de frete em tempo real.
* **FAQ por Produto:** Clientes podem fazer perguntas em páginas de produtos, que são respondidas pelo painel administrativo.

### 🚀 Painel Administrativo Completo (`/admin`)

* **Dashboard Geral:** Visão rápida com estatísticas de pedidos, produtos, promoções e contatos.
* **Gerenciamento de Produtos (CRUD):** Adicione, edite e remova produtos com um formulário completo.
* **Importador de Produtos em Massa:** Ferramenta poderosa para cadastrar ou atualizar centenas de produtos de uma vez via arquivo CSV, com análise de dados e barra de progresso em tempo real.
* **Galeria de Imagens Otimizada:** Gerencie imagens de produtos com um sistema de "lazy loading" para alta performance, permitindo reordenar (arrastando e soltando) e adicionar imagens de uma galeria central.
* **Gestão de Pedidos:** Acompanhe os orçamentos recebidos e atualize seus status.
* **Gerenciamento de Promoções:** Crie promoções baseadas em porcentagem de desconto com data de início e fim, aplicando-as a múltiplos produtos.
* **Controle Total:** Módulos para gerenciar usuários, administradores do painel e leads de contato.

## 🛠️ Tecnologias Utilizadas

* **Back-end:** PHP 8+
* **Front-end:** HTML5, CSS3, JavaScript (Vanilla)
* **Banco de Dados:** MySQL
* **Estilização:** Tailwind CSS (via CDN)
* **Bibliotecas JS:** Font Awesome, iMask.js, SortableJS

## 🏁 Como Executar o Projeto Localmente

Siga os passos abaixo para configurar e rodar o projeto no seu ambiente de desenvolvimento.

1.  **Pré-requisitos:**
    * Um ambiente de servidor local como XAMPP, WAMP ou MAMP.
    * Um sistema de gerenciamento de banco de dados como o phpMyAdmin.

2.  **Clonar o Repositório:**
    ```bash
    git clone [https://github.com/pedrobaffa/projeto-vtn-baterias.git](https://github.com/pedrobaffa/projeto-vtn-baterias.git)
    ```

3.  **Configurar o Banco de Dados:**
    * Crie um novo banco de dados no seu MySQL (ex: `grupo_vtn_db`).
    * Importe o arquivo `sqls/grupo_vtn_db.sql` para dentro do banco de dados que você acabou de criar. Ele contém toda a estrutura de tabelas e alguns dados de exemplo.

4.  **Configurar a Conexão:**
    * Na pasta `admin/`, renomeie o arquivo `config_example.php` para `config.php`.
    * Abra o novo `config.php` e edite as variáveis com as credenciais do seu banco de dados local (nome do banco, usuário e senha).

5.  **Executar o Projeto:**
    * Mova a pasta do projeto para o diretório do seu servidor local (ex: `htdocs` no XAMPP).
    * Acesse o site pelo seu navegador (ex: `http://localhost/projeto-vtn-baterias/`).

## 🔑 Acesso ao Painel Administrativo

Após a configuração, você pode acessar o painel de administração em `http://localhost/projeto-vtn-baterias/admin/`.

* **Usuário:** `admin`
* **Senha:** `admin`

---

Desenvolvido por **Pedro Baffa**.