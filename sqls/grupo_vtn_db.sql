-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 05/09/2025 às 11:28
-- Versão do servidor: 10.11.10-MariaDB-log
-- Versão do PHP: 7.2.34

-- Configurações iniciais do SQL para garantir compatibilidade.
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `grupo_vtn_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `administradores`
-- Armazena os dados de login dos usuários que podem acessar o painel de administração.
--
CREATE TABLE `administradores` (
  `id` int(11) NOT NULL,
  `usuario` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL COMMENT 'Senha criptografada com password_hash()'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `carrinho_itens`
-- Armazena os itens que cada usuário adiciona ao seu carrinho de compras/orçamento.
--
CREATE TABLE `carrinho_itens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Chave estrangeira para a tabela `usuarios`',
  `produto_sku` varchar(50) NOT NULL COMMENT 'SKU do produto, relacionado à tabela `produtos`',
  `quantidade` int(11) NOT NULL,
  `adicionado_em` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Registra quando o item foi adicionado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contatos`
-- Armazena as informações dos "leads", ou seja, os visitantes que preenchem o formulário de contato no site.
--
CREATE TABLE `contatos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `whatsapp` varchar(20) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos`
-- Armazena os dados gerais de cada pedido (orçamento) realizado por um usuário.
-- Funciona como o "cabeçalho" de uma transação.
--
CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Chave estrangeira para a tabela `usuarios`',
  `valor_total` decimal(10,2) NOT NULL COMMENT 'Soma dos valores dos itens + frete',
  `frete_tipo` varchar(100) DEFAULT NULL COMMENT 'Tipo de frete escolhido (ex: SEDEX, PAC)',
  `frete_valor` decimal(10,2) DEFAULT 0.00,
  `status` enum('novo','chamou','negociando','enviado','entregue','cancelado') NOT NULL DEFAULT 'novo' COMMENT 'Status para acompanhar o fluxo do pedido',
  `data_pedido` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedido_itens`
-- Armazena os produtos específicos de cada pedido, detalhando o que foi comprado.
--
CREATE TABLE `pedido_itens` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL COMMENT 'Chave estrangeira para a tabela `pedidos`',
  `produto_sku` varchar(50) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL COMMENT 'Preço do produto no momento da compra, para histórico',
  `preco_promocional_unitario` decimal(10,2) DEFAULT NULL COMMENT 'Preço promocional no momento da compra',
  PRIMARY KEY (`id`),
  KEY `pedido_id` (`pedido_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
-- Tabela principal que armazena todas as informações dos produtos do e-commerce.
--
CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `brand` varchar(50) NOT NULL COMMENT 'Marca do produto (ex: samsung)',
  `title` varchar(255) NOT NULL COMMENT 'Nome do produto',
  `sku` varchar(50) NOT NULL COMMENT 'Código único de identificação do produto',
  `price` decimal(10,2) NOT NULL COMMENT 'Preço base do produto',
  `promotional_price` decimal(10,2) DEFAULT NULL COMMENT 'Preço com desconto, se houver promoção ativa',
  `capacity` int(11) DEFAULT 0 COMMENT 'Capacidade da bateria (em mAh)',
  `descricao` text DEFAULT NULL COMMENT 'Descrição detalhada do produto',
  `in_stock` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Status de estoque (1 = Em estoque, 0 = Sem estoque)',
  `condicao` enum('novo','retirado') NOT NULL DEFAULT 'novo' COMMENT 'Condição do produto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_imagens`
-- Armazena os caminhos das imagens associadas a cada produto. Permite múltiplas imagens por produto.
--
CREATE TABLE `produto_imagens` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL COMMENT 'Chave estrangeira para a tabela `produtos`',
  `image_path` varchar(255) NOT NULL COMMENT 'Caminho do arquivo da imagem',
  `ordem` int(11) NOT NULL DEFAULT 0 COMMENT 'Define a ordem de exibição das imagens'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_promocao`
-- Tabela de ligação (pivô) que conecta produtos a promoções. Essencial para relações Muitos-para-Muitos.
--
CREATE TABLE `produto_promocao` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL COMMENT 'Chave estrangeira para a tabela `produtos`',
  `promocao_id` int(11) NOT NULL COMMENT 'Chave estrangeira para a tabela `promocoes`'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `promocoes`
-- Armazena as regras das promoções, como nome, percentual de desconto e período de validade.
--
CREATE TABLE `promocoes` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `percentual_desconto` decimal(5,2) NOT NULL,
  `data_inicio` datetime NOT NULL,
  `data_fim` datetime NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Permite desativar uma promoção sem apagá-la'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
-- Armazena os dados dos clientes que se cadastram no site.
--
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `sobrenome` varchar(100) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL COMMENT 'Senha criptografada com password_hash()',
  `telefone` varchar(20) NOT NULL,
  `endereco` text DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas (Otimizações e Restrições)
--

-- --- Índices de tabela `administradores` ---
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`); -- Garante que não existam dois admins com o mesmo nome de usuário.

-- --- Índices de tabela `carrinho_itens` ---
ALTER TABLE `carrinho_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`); -- Otimiza a busca de itens de carrinho por usuário.

-- --- Índices de tabela `contatos` ---
ALTER TABLE `contatos`
  ADD PRIMARY KEY (`id`);
  
-- --- Índices de tabela `pedidos` ---
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

-- --- Índices de tabela `pedido_itens` ---
ALTER TABLE `pedido_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`);

-- --- Índices de tabela `produtos` ---
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`); -- Garante que cada produto tenha um SKU único.

-- --- Índices de tabela `produto_imagens` ---
ALTER TABLE `produto_imagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produto_id` (`produto_id`); -- Otimiza a busca de imagens por produto.

-- --- Índices de tabela `produto_promocao` ---
ALTER TABLE `produto_promocao`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `produto_promocao_unique` (`produto_id`,`promocao_id`), -- Impede que o mesmo produto seja adicionado duas vezes na mesma promoção.
  ADD KEY `fk_promocao_id` (`promocao_id`);

-- --- Índices de tabela `promocoes` ---
ALTER TABLE `promocoes`
  ADD PRIMARY KEY (`id`);

-- --- Índices de tabela `usuarios` ---
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`), -- Garante que cada CPF seja único no sistema.
  ADD UNIQUE KEY `email` (`email`); -- Garante que cada e-mail seja único no sistema.

--
-- AUTO_INCREMENT para tabelas despejadas
-- Define os valores iniciais para as chaves primárias autoincrementais.
--
ALTER TABLE `administradores` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `carrinho_itens` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `contatos` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `pedidos` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `pedido_itens` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `produtos` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `produto_imagens` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `produto_promocao` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `promocoes` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `usuarios` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas (Chaves Estrangeiras)
--

-- --- Restrições para tabelas `carrinho_itens` ---
ALTER TABLE `carrinho_itens`
  ADD CONSTRAINT `fk_usuario_carrinho` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
  -- Se um usuário for deletado, todos os itens do seu carrinho também serão (ON DELETE CASCADE).

-- --- Restrições para tabela `pedidos` ---
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedidos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
  -- Se um usuário for deletado, todos os seus pedidos também serão.

-- --- Restrições para tabela `pedido_itens` ---
ALTER TABLE `pedido_itens`
  ADD CONSTRAINT `fk_itens_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE;
  -- Se um pedido for deletado, todos os seus itens também serão.

-- --- Restrições para tabelas `produto_imagens` ---
ALTER TABLE `produto_imagens`
  ADD CONSTRAINT `produto_imagens_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
  -- Se um produto for deletado, todas as suas imagens também serão.

-- --- Restrições para tabelas `produto_promocao` ---
ALTER TABLE `produto_promocao`
  ADD CONSTRAINT `fk_produto_promocao_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_produto_promocao_promocao` FOREIGN KEY (`promocao_id`) REFERENCES `promocoes` (`id`) ON DELETE CASCADE;
  -- Se um produto ou uma promoção forem deletados, a associação entre eles é removida.
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
