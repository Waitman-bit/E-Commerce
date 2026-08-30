-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 30/08/2026 às 19:32
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `tcc`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categoria`
--

CREATE TABLE `categoria` (
  `id_categoria` int(11) NOT NULL,
  `nome` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categoria`
--

INSERT INTO `categoria` (`id_categoria`, `nome`) VALUES
(1, 'Futebol'),
(2, 'Basquete'),
(3, 'Corrida'),
(4, 'Musculação'),
(5, 'Natação'),
(6, 'Suplementos'),
(7, 'Vestuário'),
(8, 'Acessórios'),
(9, 'Artes Marciais');

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas_receber`
--

CREATE TABLE `contas_receber` (
  `id` int(11) NOT NULL,
  `data_vencimento` date NOT NULL,
  `numero_parcela` int(11) NOT NULL,
  `valor_parcela` decimal(10,2) NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `id_pedido` int(11) NOT NULL,
  `valor_pago` decimal(10,2) DEFAULT NULL,
  `valor_pago_posven` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `entrega`
--

CREATE TABLE `entrega` (
  `id_entrega` int(11) NOT NULL,
  `endereco` varchar(150) NOT NULL,
  `estado` varchar(50) NOT NULL,
  `cidade` varchar(50) NOT NULL,
  `cep` varchar(10) NOT NULL,
  `status` varchar(50) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `frete` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `entrega`
--

INSERT INTO `entrega` (`id_entrega`, `endereco`, `estado`, `cidade`, `cep`, `status`, `id_pedido`, `frete`) VALUES
(1, 'Abilio Correa, 2 - talavassw', 'sp', 'taquagueticetuba', '15902-222', 'Aguardando envio', 1, 14.90);

-- --------------------------------------------------------

--
-- Estrutura para tabela `item_pedido`
--

CREATE TABLE `item_pedido` (
  `id` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `quantidade` int(11) DEFAULT NULL,
  `preco_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `item_pedido`
--

INSERT INTO `item_pedido` (`id`, `id_produto`, `id_pedido`, `quantidade`, `preco_unitario`) VALUES
(1, 19, 1, 1, 1599.90),
(2, 14, 1, 1, 129.90);

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedido`
--

CREATE TABLE `pedido` (
  `id_pedido` int(11) NOT NULL,
  `data_pedido` datetime NOT NULL,
  `status_pedido` varchar(50) NOT NULL,
  `valor_total` decimal(10,2) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo_pagamento` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pedido`
--

INSERT INTO `pedido` (`id_pedido`, `data_pedido`, `status_pedido`, `valor_total`, `id_usuario`, `tipo_pagamento`) VALUES
(1, '2026-08-06 09:50:24', 'Confirmado', 1744.70, 8, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto`
--

CREATE TABLE `produto` (
  `id_produto` int(11) NOT NULL,
  `marca` varchar(20) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `dimensoes` decimal(10,2) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `genero` varchar(9) NOT NULL,
  `estoque` int(11) DEFAULT NULL,
  `descricao` varchar(50) NOT NULL,
  `imagem` varchar(300) NOT NULL,
  `peso` decimal(10,2) NOT NULL,
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produto`
--

INSERT INTO `produto` (`id_produto`, `marca`, `id_categoria`, `dimensoes`, `nome`, `genero`, `estoque`, `descricao`, `imagem`, `peso`, `preco`) VALUES
(1, 'Generica', 3, 0.00, 'Tenis Asics', 'Unissex', 10, 'Tenis de corrida', 'produto_6a4d896d10ffe9.81408047.webp', 0.00, 300.00),
(2, 'Generica', 1, 0.00, 'Camisa Santos Charli', 'Unissex', 10, 'Camisa esportiva', 'produto_6a4d8af08ff304.10963099.jpg', 0.00, 250.00),
(3, 'Generica', 1, 0.00, 'Luva Goleiro', 'Unissex', 10, 'Luvas de goleiro', 'produto_6a4d8f528ebd64.33366870.jpg', 0.00, 150.00),
(4, 'Generica', 1, 0.00, 'Chuteira Mercurial N', 'Unissex', 10, 'Chuteira de campo', 'produto_6a4d8fc4587534.04441288.jpg', 0.00, 670.00),
(5, 'Nike', 1, 30.00, 'Camisa Brasil 2022', 'Unissex', 30, 'Camisa oficial da Maior Seleção do Mundo', 'camisa_brasil.jpg', 0.25, 349.90),
(6, 'Adidas', 1, 30.00, 'Camisa Real Madrid', 'Masculino', 20, 'Camisa oficial', 'real_madrid.jpg', 0.25, 399.90),
(7, 'Umbro', 1, 22.00, 'Bola Campo Pro', 'Unissex', 40, 'Bola profissional', 'bola_umbro.jpg', 0.45, 199.90),
(8, 'Penalty', 1, 18.00, 'Caneleira Matis', 'Unissex', 60, 'Proteção para jogo', 'caneleira.jpg', 0.30, 69.90),
(9, 'Poker', 1, 20.00, 'Luva Goleiro Pro', 'Unissex', 15, 'Luva profissional', 'luva_goleiro.jpg', 0.45, 249.90),
(10, 'Nike', 2, 34.00, 'Bola Basquete Elite', 'Unissex', 22, 'Bola oficial', 'bola_basquete.jpg', 0.60, 239.90),
(11, 'Spalding', 2, 33.00, 'Bola NBA', 'Unissex', 18, 'Modelo NBA', 'bola_nba.jpg', 0.62, 299.90),
(12, 'Jordan 4', 2, 36.00, 'Tênis Jordan 4', 'Masculino', 8, 'Tênis basquete', 'jordan_four.jpg', 0.95, 899.90),
(13, 'Adidas', 2, 35.00, 'Tênis Harden Step', 'Masculino', 10, 'Tênis esportivo próprio para basquete', 'harden.jpg', 0.90, 749.90),
(14, 'Nike', 2, 30.00, 'Regata Basketball', 'Masculino', 24, 'Regata esportiva', 'regata_basket.jpg', 0.20, 129.90),
(15, 'Olympikus', 3, 35.00, 'Corre 4', 'Masculino', 18, 'Tênis corrida', 'corre4.jpg', 0.75, 499.90),
(16, 'Asics', 3, 36.00, 'Gel Nimbus', 'Unissex', 12, 'Amortecimento premium', 'gel_nimbus.jpg', 0.82, 999.90),
(17, 'Nike', 3, 35.00, 'Pegasus 42', 'Masculino', 15, 'Tênis corrida', 'pegasus42.jpg', 0.78, 799.90),
(18, 'Adidas', 3, 34.00, 'Adizero SL', 'Feminino', 20, 'Tênis leve', 'adizero.jpg', 0.75, 699.90),
(19, 'Garmin', 3, 12.00, 'Relógio Forerunner', 'Unissex', 6, 'GPS esportivo', 'forerunner.jpg', 0.18, 1599.90),
(20, 'Acte', 4, 100.00, 'Colchonete EVA', 'Unissex', 35, 'Colchonete fitness', 'colchonete.jpg', 1.10, 89.90),
(21, 'Vollo', 4, 30.00, 'Par Halteres 5kg', 'Unissex', 20, 'Halter em ferro', 'halter5kg.jpg', 10.00, 199.90),
(22, 'Muvin', 4, 20.00, 'Faixa Elástica', 'Unissex', 45, 'Faixa resistência', 'faixa.jpg', 0.20, 49.90),
(23, 'Acte', 4, 25.00, 'Corda de Pular', 'Unissex', 50, 'Corda ajustável', 'corda.jpg', 0.30, 39.90),
(24, 'Kikos', 4, 45.00, 'Roda Abdominal', 'Unissex', 18, 'Treino abdominal', 'abwheel.jpg', 0.80, 119.90),
(25, 'Speedo', 5, 15.00, 'Óculos Hydro', 'Unissex', 30, 'Óculos natação', 'oculos.jpg', 0.08, 99.90),
(26, 'Speedo', 5, 28.00, 'Touca Silicone', 'Unissex', 45, 'Touca profissional', 'touca.jpg', 0.05, 39.90),
(27, 'Arena', 5, 18.00, 'Óculos Cobra', 'Unissex', 15, 'Alta performance', 'cobra.jpg', 0.07, 299.90),
(28, 'Hammerhead', 5, 32.00, 'Maiô Training', 'Feminino', 12, 'Maiô esportivo', 'maio.jpg', 0.25, 179.90),
(29, 'Speedo', 5, 30.00, 'Sunga Basic', 'Masculino', 20, 'Sunga esportiva', 'sunga.jpg', 0.15, 79.90),
(30, 'IntegralMedica', 6, 25.00, 'Whey Protein 900g', 'Unissex', 25, 'Proteína concentrada', 'whey900.jpg', 0.90, 149.90),
(31, 'Max Titanium', 6, 30.00, 'Creatina 300g', 'Unissex', 40, 'Creatina monohidratada', 'creatina.jpg', 0.30, 99.90),
(32, 'Growth', 6, 25.00, 'Whey Isolado', 'Unissex', 18, 'Proteína isolada', 'whey_iso.jpg', 1.00, 239.90),
(33, 'Dark Lab', 6, 20.00, 'Pré Treino', 'Unissex', 22, 'Energia treino', 'pretreino.jpg', 0.30, 89.90),
(34, 'Universal', 6, 22.00, 'BCAA 240 Caps', 'Unissex', 14, 'Aminoácidos', 'bcaa.jpg', 0.45, 129.90),
(35, 'Nike', 7, 28.00, 'Short Dri-FIT', 'Masculino', 30, 'Short esportivo', 'short_nike.jpg', 0.20, 119.90),
(36, 'Adidas', 7, 28.00, 'Calça Moletom', 'Unissex', 18, 'Calça esportiva', 'calca.jpg', 0.55, 199.90),
(37, 'Puma', 7, 30.00, 'Jaqueta Corta Vento', 'Unissex', 14, 'Jaqueta leve', 'jaqueta.jpg', 0.60, 299.90),
(38, 'Fila', 7, 27.00, 'Camiseta Basic', 'Feminino', 35, 'Camiseta esportiva', 'camiseta_fila.jpg', 0.22, 89.90),
(39, 'Under Armour', 7, 26.00, 'Meia Performance', 'Unissex', 50, 'Par de meias', 'meia.jpg', 0.10, 39.90),
(40, 'Mizuno', 8, 45.00, 'Mochila Sport', 'Unissex', 18, 'Mochila resistente', 'mochila.jpg', 0.90, 199.90),
(41, 'Nike', 8, 12.00, 'Garrafa 750ml', 'Unissex', 40, 'Garrafa Squeeze esportiva', 'garrafa.jpg', 0.18, 59.90),
(42, 'Acte', 8, 18.00, 'Munhequeira', 'Unissex', 35, 'Suporte punho', 'munhequeira.jpg', 0.10, 34.90),
(43, 'Poker', 8, 15.00, 'Faixa Capitão', 'Unissex', 25, 'Faixa elástica', 'capitao.jpg', 0.05, 24.90),
(44, 'Adidas', 7, 25.00, 'Boné Running', 'Unissex', 20, 'Boné esportivo', 'bone.jpg', 0.12, 70.00),
(45, 'Everlast', 9, 18.00, 'Luva de Boxe Pro', 'Unissex', 20, 'Luva para treino', 'luva_boxe.jpg', 0.80, 299.90),
(46, 'Adidas', 9, 20.00, 'Kimono Jiu-Jitsu', 'Unissex', 15, 'Kimono profissional', 'kimono_bjj.jpg', 1.60, 449.90),
(47, 'Venum', 9, 16.00, 'Short Muay Thai', 'Masculino', 25, 'Short para luta', 'short_muaythai.jpg', 0.25, 179.90),
(48, 'Pretorian', 9, 12.00, 'Bandagem Elástica', 'Unissex', 50, 'Bandagem para mãos', 'bandagem.jpg', 0.10, 39.90),
(50, 'Everlast', 9, 15.00, 'Caneleira Muay Thai', 'Unissex', 18, 'Proteção para pernas', 'caneleira_muaythai.jpg', 0.70, 229.90),
(51, 'BoomBoxe', 9, 18.00, 'Boneco Simulador Bob', 'Unissex', 12, 'Treino de golpes', 'bob.jpg', 0.90, 349.90),
(52, 'Adidas', 9, 25.00, 'Faixa Preta Jiu-Jitsu', 'Unissex', 10, 'Faixa oficial', 'faixa_preta.jpg', 0.15, 99.90),
(53, 'Vollo', 9, 30.00, 'Saco de Pancadas 90cm', 'Unissex', 8, 'Saco de treino', 'saco_pancadas.jpg', 18.00, 499.90),
(54, 'Everlast', 9, 14.00, 'Corda de Velocidade', 'Unissex', 30, 'Corda para treino', 'corda_boxe.jpg', 0.30, 69.90),
(55, 'Generica', 1, 0.00, 'Chuteira Nike Phantom Luna II Academy Campo Masculina - Branco', 'Masculino', 10, 'Chuteira Nike Phantom Luna II', 'produto_6a739161e611a0.20579811.jpg', 0.00, 400.00),
(56, 'Generica', 3, 0.00, 'Viseira Para Corrida Hupi Run For Fun Preta', 'Unissex', 10, 'Viseira Para Corrida Hupi Run For Fun Preta', 'produto_6a73a5a49abf93.12801221.jpg', 0.00, 55.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `recuperacao_senha`
--

CREATE TABLE `recuperacao_senha` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `codigo_hash` varchar(255) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(40) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `senha` varchar(100) NOT NULL,
  `tipo` enum('admin','cliente') NOT NULL DEFAULT 'cliente',
  `cep` varchar(9) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nome`, `cpf`, `email`, `telefone`, `senha`, `tipo`, `cep`, `foto_perfil`) VALUES
(1, 'Davi Waitman', NULL, 'teste@gmail.com', '(16) 99999-9999', '$2y$10$upOA080..VIRJw6QRPzfGu8FLUUXwPY661uEBB6kuQcTB9kxZRCVe', 'cliente', NULL, NULL),
(8, 'Administrador', '123.456.789-00', 'admin@gmail.com', '(16) 99999-9999', '$2y$10$yqLHGXs607j.bmL7nYhx8.y6A7iBkKDl.XAOdCwTgPIve7mO5iN7i', 'admin', '15900-000', NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Índices de tabela `contas_receber`
--
ALTER TABLE `contas_receber`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_contas_receber_pedido` (`id_pedido`);

--
-- Índices de tabela `entrega`
--
ALTER TABLE `entrega`
  ADD PRIMARY KEY (`id_entrega`),
  ADD KEY `fk_entrega_pedido` (`id_pedido`);

--
-- Índices de tabela `item_pedido`
--
ALTER TABLE `item_pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_id_produto` (`id_produto`),
  ADD KEY `fk_id_pedido` (`id_pedido`);

--
-- Índices de tabela `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `id_cliente` (`id_usuario`);

--
-- Índices de tabela `produto`
--
ALTER TABLE `produto`
  ADD PRIMARY KEY (`id_produto`);

--
-- Índices de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_recuperacao_usuario` (`id_usuario`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `contas_receber`
--
ALTER TABLE `contas_receber`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `entrega`
--
ALTER TABLE `entrega`
  MODIFY `id_entrega` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `item_pedido`
--
ALTER TABLE `item_pedido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `pedido`
--
ALTER TABLE `pedido`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `produto`
--
ALTER TABLE `produto`
  MODIFY `id_produto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `contas_receber`
--
ALTER TABLE `contas_receber`
  ADD CONSTRAINT `fk_contas_receber_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`);

--
-- Restrições para tabelas `entrega`
--
ALTER TABLE `entrega`
  ADD CONSTRAINT `fk_entrega_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`);

--
-- Restrições para tabelas `item_pedido`
--
ALTER TABLE `item_pedido`
  ADD CONSTRAINT `fk_id_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`),
  ADD CONSTRAINT `fk_id_produto` FOREIGN KEY (`id_produto`) REFERENCES `produto` (`id_produto`);

--
-- Restrições para tabelas `pedido`
--
ALTER TABLE `pedido`
  ADD CONSTRAINT `pedido_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Restrições para tabelas `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD CONSTRAINT `fk_recuperacao_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
