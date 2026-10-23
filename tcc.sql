-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 23/10/2026 às 02:53
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
  `valor_pago_posven` decimal(10,2) DEFAULT NULL,
  `mp_payment_id` bigint(20) DEFAULT NULL,
  `asaas_payment_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `contas_receber`
--

INSERT INTO `contas_receber` (`id`, `data_vencimento`, `numero_parcela`, `valor_parcela`, `data_pagamento`, `id_pedido`, `valor_pago`, `valor_pago_posven`, `mp_payment_id`, `asaas_payment_id`) VALUES
(1, '2027-10-17', 1, 2210.00, NULL, 2, NULL, NULL, NULL, 'pay_d51ip3ehz565ptqq'),
(2, '2027-10-17', 1, 6610.00, NULL, 3, NULL, NULL, NULL, 'pay_fecpkdgpglejitjw');

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
(1, 'Abilio Correa, 2 - talavassw', 'sp', 'taquagueticetuba', '15902-222', 'Aguardando envio', 1, 14.90),
(2, 'Rua Abílio Corrêa Gomes, 2', 'SP', 'Taquaritinga', '15902222', 'Pendente', 2, 10.00),
(3, 'Rua Abílio Corrêa Gomes, 2', 'SP', 'Taquaritinga', '15902222', 'Pendente', 3, 10.00);

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
(2, 14, 1, 1, 129.90),
(3, 57, 2, 1, 2200.00),
(4, 57, 3, 3, 2200.00);

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
(1, '2026-08-06 09:50:24', 'Confirmado', 1744.70, 8, NULL),
(2, '2026-10-17 12:12:49', 'Aguardando pagamento', 2210.00, 9, 'pix'),
(3, '2026-10-17 12:18:50', 'Aguardando pagamento', 6610.00, 9, 'pix');

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
  `descricao` varchar(50) NOT NULL,
  `imagem` varchar(300) NOT NULL,
  `peso` decimal(10,2) NOT NULL,
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produto`
--

INSERT INTO `produto` (`id_produto`, `marca`, `id_categoria`, `dimensoes`, `nome`, `genero`, `descricao`, `imagem`, `peso`, `preco`) VALUES
(1, 'Generica', 3, 0.00, 'Tenis Asics', 'Unissex', 'Tenis de corrida', 'produto_6a4d896d10ffe9.81408047.webp', 0.00, 300.00),
(2, 'Generica', 1, 0.00, 'Camisa Santos Charli', 'Unissex', 'Camisa esportiva', 'produto_6a4d8af08ff304.10963099.jpg', 0.00, 250.00),
(3, 'Generica', 1, 0.00, 'Luva Goleiro', 'Unissex', 'Luvas de goleiro', 'produto_6a4d8f528ebd64.33366870.jpg', 0.00, 150.00),
(4, 'Generica', 1, 0.00, 'Chuteira Mercurial N', 'Unissex', 'Chuteira de campo', 'produto_6a4d8fc4587534.04441288.jpg', 0.00, 670.00),
(5, 'Nike', 1, 30.00, 'Camisa Brasil 2022', 'Unissex', 'Camisa oficial da Maior Seleção do Mundo', 'camisa_brasil.jpg', 0.25, 349.90),
(6, 'Adidas', 1, 30.00, 'Camisa Real Madrid', 'Masculino', 'Camisa oficial', 'real_madrid.jpg', 0.25, 399.90),
(7, 'Umbro', 1, 22.00, 'Bola Campo Pro', 'Unissex', 'Bola profissional', 'bola_umbro.jpg', 0.45, 199.90),
(8, 'Penalty', 1, 18.00, 'Caneleira Matis', 'Unissex', 'Proteção para jogo', 'caneleira.jpg', 0.30, 69.90),
(9, 'Poker', 1, 20.00, 'Luva Goleiro Pro', 'Unissex', 'Luva profissional', 'luva_goleiro.jpg', 0.45, 249.90),
(10, 'Nike', 2, 34.00, 'Bola Basquete Elite', 'Unissex', 'Bola oficial', 'bola_basquete.jpg', 0.60, 239.90),
(11, 'Spalding', 2, 33.00, 'Bola NBA', 'Unissex', 'Modelo NBA', 'bola_nba.jpg', 0.62, 299.90),
(12, 'Jordan 4', 2, 36.00, 'Tênis Jordan 4', 'Masculino', 'Tênis basquete', 'jordan_four.jpg', 0.95, 899.90),
(13, 'Adidas', 2, 35.00, 'Tênis Harden Step', 'Masculino', 'Tênis esportivo próprio para basquete', 'harden.jpg', 0.90, 749.90),
(14, 'Nike', 2, 30.00, 'Regata Basketball', 'Masculino', 'Regata esportiva', 'regata_basket.jpg', 0.20, 129.90),
(15, 'Olympikus', 3, 35.00, 'Corre 4', 'Masculino', 'Tênis corrida', 'corre4.jpg', 0.75, 499.90),
(16, 'Asics', 3, 36.00, 'Gel Nimbus', 'Unissex', 'Amortecimento premium', 'gel_nimbus.jpg', 0.82, 999.90),
(17, 'Nike', 3, 35.00, 'Pegasus 42', 'Masculino', 'Tênis corrida', 'pegasus42.jpg', 0.78, 799.90),
(18, 'Adidas', 3, 34.00, 'Adizero SL', 'Feminino', 'Tênis leve', 'adizero.jpg', 0.75, 699.90),
(19, 'Garmin', 3, 12.00, 'Relógio Forerunner', 'Unissex', 'GPS esportivo', 'forerunner.jpg', 0.18, 1599.90),
(20, 'Acte', 4, 100.00, 'Colchonete EVA', 'Unissex', 'Colchonete fitness', 'colchonete.jpg', 1.10, 89.90),
(21, 'Vollo', 4, 30.00, 'Par Halteres 5kg', 'Unissex', 'Halter em ferro', 'halter5kg.jpg', 10.00, 199.90),
(22, 'Muvin', 4, 20.00, 'Faixa Elástica', 'Unissex', 'Faixa resistência', 'faixa.jpg', 0.20, 49.90),
(23, 'Acte', 4, 25.00, 'Corda de Pular', 'Unissex', 'Corda ajustável', 'corda.jpg', 0.30, 39.90),
(24, 'Kikos', 4, 45.00, 'Roda Abdominal', 'Unissex', 'Treino abdominal', 'abwheel.jpg', 0.80, 119.90),
(25, 'Speedo', 5, 15.00, 'Óculos Hydro', 'Unissex', 'Óculos natação', 'oculos.jpg', 0.08, 99.90),
(26, 'Speedo', 5, 28.00, 'Touca Silicone', 'Unissex', 'Touca profissional', 'touca.jpg', 0.05, 39.90),
(27, 'Arena', 5, 18.00, 'Óculos Cobra', 'Unissex', 'Alta performance', 'cobra.jpg', 0.07, 299.90),
(28, 'Hammerhead', 5, 32.00, 'Maiô Training', 'Feminino', 'Maiô esportivo', 'maio.jpg', 0.25, 179.90),
(29, 'Speedo', 5, 30.00, 'Sunga Basic', 'Masculino', 'Sunga esportiva', 'sunga.jpg', 0.15, 79.90),
(30, 'IntegralMedica', 6, 25.00, 'Whey Protein 900g', 'Unissex', 'Proteína concentrada', 'whey900.jpg', 0.90, 149.90),
(31, 'Max Titanium', 6, 30.00, 'Creatina 300g', 'Unissex', 'Creatina monohidratada', 'creatina.jpg', 0.30, 99.90),
(32, 'Growth', 6, 25.00, 'Whey Isolado', 'Unissex', 'Proteína isolada', 'whey_iso.jpg', 1.00, 239.90),
(33, 'Dark Lab', 6, 20.00, 'Pré Treino', 'Unissex', 'Energia treino', 'pretreino.jpg', 0.30, 89.90),
(34, 'Universal', 6, 22.00, 'BCAA 240 Caps', 'Unissex', 'Aminoácidos', 'bcaa.jpg', 0.45, 129.90),
(35, 'Nike', 7, 28.00, 'Short Dri-FIT', 'Masculino', 'Short esportivo', 'short_nike.jpg', 0.20, 119.90),
(36, 'Adidas', 7, 28.00, 'Calça Moletom', 'Unissex', 'Calça esportiva', 'calca.jpg', 0.55, 199.90),
(37, 'Puma', 7, 30.00, 'Jaqueta Corta Vento', 'Unissex', 'Jaqueta leve', 'jaqueta.jpg', 0.60, 299.90),
(38, 'Fila', 7, 27.00, 'Camiseta Basic', 'Feminino', 'Camiseta esportiva', 'camiseta_fila.jpg', 0.22, 89.90),
(39, 'Under Armour', 7, 26.00, 'Meia Performance', 'Unissex', 'Par de meias', 'meia.jpg', 0.10, 39.90),
(40, 'Mizuno', 8, 45.00, 'Mochila Sport', 'Unissex', 'Mochila resistente', 'mochila.jpg', 0.90, 199.90),
(41, 'Nike', 8, 12.00, 'Garrafa 750ml', 'Unissex', 'Garrafa Squeeze esportiva', 'garrafa.jpg', 0.18, 59.90),
(42, 'Acte', 8, 18.00, 'Munhequeira', 'Unissex', 'Suporte punho', 'munhequeira.jpg', 0.10, 34.90),
(43, 'Poker', 8, 15.00, 'Faixa Capitão', 'Unissex', 'Faixa elástica', 'capitao.jpg', 0.05, 24.90),
(44, 'Adidas', 7, 25.00, 'Boné Running', 'Unissex', 'Boné esportivo', 'bone.jpg', 0.12, 70.00),
(45, 'Everlast', 9, 18.00, 'Luva de Boxe Pro', 'Unissex', 'Luva para treino', 'luva_boxe.jpg', 0.80, 299.90),
(46, 'Adidas', 9, 20.00, 'Kimono Jiu-Jitsu', 'Unissex', 'Kimono profissional', 'kimono_bjj.jpg', 1.60, 449.90),
(47, 'Venum', 9, 16.00, 'Short Muay Thai', 'Masculino', 'Short para luta', 'short_muaythai.jpg', 0.25, 179.90),
(48, 'Pretorian', 9, 12.00, 'Bandagem Elástica', 'Unissex', 'Bandagem para mãos', 'bandagem.jpg', 0.10, 39.90),
(50, 'Everlast', 9, 15.00, 'Caneleira Muay Thai', 'Unissex', 'Proteção para pernas', 'caneleira_muaythai.jpg', 0.70, 229.90),
(51, 'BoomBoxe', 9, 18.00, 'Boneco Simulador Bob', 'Unissex', 'Treino de golpes', 'bob.jpg', 0.90, 349.90),
(52, 'Adidas', 9, 25.00, 'Faixa Preta Jiu-Jitsu', 'Unissex', 'Faixa oficial', 'faixa_preta.jpg', 0.15, 99.90),
(53, 'Vollo', 9, 30.00, 'Saco de Pancadas 90cm', 'Unissex', 'Saco de treino', 'saco_pancadas.jpg', 18.00, 499.90),
(54, 'Everlast', 9, 14.00, 'Corda de Velocidade', 'Unissex', 'Corda para treino', 'corda_boxe.jpg', 0.30, 69.90),
(55, 'Generica', 1, 0.00, 'Chuteira Nike Phantom Luna II Academy Campo Masculina - Branco', 'Masculino', 'Chuteira Nike Phantom Luna II', 'produto_6a739161e611a0.20579811.jpg', 0.00, 400.00),
(56, 'Generica', 3, 0.00, 'Viseira Para Corrida Hupi Run For Fun Preta', 'Unissex', 'Viseira Para Corrida Hupi Run For Fun Preta', 'produto_6a73a5a49abf93.12801221.jpg', 0.00, 55.00),
(57, 'Generica', 1, 0.00, 'Chuteira Campo Umbro Adamant Master Class Pro', 'Unissex', 'chuteira de alta performance', 'produto_6ad38e40188f50.62532720.jpeg', 0.00, 2200.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_tamanho`
--

CREATE TABLE `produto_tamanho` (
  `id_produto` int(11) NOT NULL,
  `id_tamanho` int(11) NOT NULL,
  `estoque` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produto_tamanho`
--

INSERT INTO `produto_tamanho` (`id_produto`, `id_tamanho`, `estoque`) VALUES
(1, 1, 10),
(1, 2, 7),
(1, 3, 7),
(1, 4, 8),
(1, 5, 9),
(1, 6, 7),
(1, 7, 8),
(1, 8, 10),
(1, 9, 6),
(1, 10, 10),
(1, 11, 10),
(2, 12, 14),
(2, 13, 12),
(2, 14, 9),
(2, 15, 12),
(2, 16, 15),
(2, 17, 15),
(3, 18, 10),
(4, 1, 5),
(4, 2, 11),
(4, 3, 9),
(4, 4, 9),
(4, 5, 10),
(4, 6, 10),
(4, 7, 10),
(4, 8, 6),
(4, 9, 9),
(4, 10, 18),
(4, 11, 10),
(5, 12, 8),
(5, 13, 23),
(5, 14, 8),
(5, 15, 7),
(5, 16, 12),
(5, 17, 9),
(6, 12, 11),
(6, 13, 20),
(6, 14, 11),
(6, 15, 17),
(6, 16, 7),
(6, 17, 14),
(7, 18, 10),
(8, 13, 8),
(8, 14, 9),
(8, 15, 10),
(9, 18, 10),
(10, 18, 10),
(11, 18, 10),
(12, 1, 11),
(12, 2, 15),
(12, 3, 9),
(12, 4, 5),
(12, 5, 7),
(12, 6, 6),
(12, 7, 0),
(12, 8, 9),
(12, 9, 9),
(12, 10, 10),
(12, 11, 9),
(13, 1, 11),
(13, 2, 7),
(13, 3, 8),
(13, 4, 8),
(13, 5, 14),
(13, 6, 6),
(13, 7, 13),
(13, 8, 8),
(13, 9, 9),
(13, 10, 11),
(13, 11, 7),
(14, 18, 10),
(15, 1, 24),
(15, 2, 17),
(15, 3, 15),
(15, 4, 12),
(15, 5, 14),
(15, 6, 12),
(15, 7, 9),
(15, 8, 10),
(15, 9, 11),
(15, 10, 12),
(15, 11, 12),
(16, 1, 9),
(16, 2, 9),
(16, 3, 7),
(16, 4, 9),
(16, 5, 10),
(16, 6, 4),
(16, 7, 10),
(16, 8, 7),
(16, 9, 6),
(16, 10, 7),
(16, 11, 13),
(17, 1, 9),
(17, 2, 9),
(17, 3, 7),
(17, 4, 5),
(17, 5, 6),
(17, 6, 8),
(17, 7, 10),
(17, 8, 6),
(17, 9, 7),
(17, 10, 9),
(17, 11, 10),
(18, 1, 32),
(18, 2, 39),
(18, 3, 22),
(18, 4, 11),
(18, 5, 12),
(18, 6, 0),
(18, 7, 7),
(18, 8, 22),
(18, 9, 19),
(18, 10, 10),
(18, 11, 18),
(19, 18, 10),
(20, 18, 10),
(21, 18, 10),
(22, 18, 10),
(23, 18, 10),
(24, 18, 10),
(25, 18, 10),
(26, 18, 10),
(27, 18, 10),
(28, 18, 10),
(29, 18, 10),
(30, 18, 10),
(31, 18, 10),
(32, 18, 10),
(33, 18, 10),
(34, 18, 10),
(35, 18, 10),
(36, 12, 7),
(36, 13, 22),
(36, 14, 6),
(36, 15, 11),
(36, 16, 10),
(36, 17, 10),
(37, 18, 10),
(38, 12, 9),
(38, 13, 13),
(38, 14, 9),
(38, 15, 7),
(38, 16, 11),
(38, 17, 9),
(39, 18, 10),
(40, 18, 10),
(41, 18, 10),
(42, 18, 10),
(43, 18, 10),
(44, 18, 10),
(45, 18, 10),
(46, 18, 10),
(47, 18, 10),
(48, 18, 10),
(50, 13, 8),
(50, 14, 12),
(50, 15, 12),
(51, 18, 10),
(52, 18, 10),
(53, 18, 10),
(54, 18, 10),
(55, 1, 11),
(55, 4, 12),
(55, 5, 13),
(55, 6, 7),
(55, 8, 11),
(55, 9, 12),
(55, 10, 7),
(55, 11, 12),
(56, 18, 10),
(57, 1, 11),
(57, 2, 21),
(57, 3, 22),
(57, 4, 21),
(57, 5, 10),
(57, 6, 17),
(57, 7, 17),
(57, 8, 6),
(57, 9, 10),
(57, 10, 9),
(57, 11, 0);

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

--
-- Despejando dados para a tabela `recuperacao_senha`
--

INSERT INTO `recuperacao_senha` (`id`, `id_usuario`, `codigo_hash`, `expira_em`, `usado`, `criado_em`) VALUES
(1, 1, '$2y$10$Gc9/7r5t/KrLOGAMq6dRb.4pcdwcixrjMZmqpF6DLfs1TXNdCAP7i', '2026-08-31 19:46:48', 0, '2026-08-31 14:36:48'),
(2, 1, '$2y$10$aafz8w35QbgDGDsvJm6AOOgGphyD3/dOPwhUEEB1F48eUtHXrjziu', '2026-08-31 19:49:49', 0, '2026-08-31 14:39:49'),
(3, 1, '$2y$10$xnsJNU550gjwyC4WmGIq5uuNU9QJLoZMJqjLR7sf2Ky33L9HYF7s6', '2026-08-31 19:49:56', 0, '2026-08-31 14:39:56'),
(4, 1, '$2y$10$DbPtxgdnVqBOeETsGlyV5u2PBrEXaEOMxvSZtF7/mbvVOtxcKJE8C', '2026-08-31 19:50:21', 0, '2026-08-31 14:40:21'),
(5, 1, '$2y$10$qhMM7A9sR/597DKd/5ZG4uBD7xKmUZvCHtA2IPVaKkIgr735zTWCK', '2026-08-31 19:50:51', 0, '2026-08-31 14:40:51'),
(6, 1, '$2y$10$jKSO2Nz.lFmCXR28vpDlQu0sX6vHD0SnwieVmLs/2WENgYq6Tw5Li', '2026-08-31 20:01:19', 1, '2026-08-31 14:51:19'),
(7, 1, '$2y$10$S2OSZjjsQuzM18OGxrIrB.bDCFBMlVMIrsWcBE0lwfbYb/BEC6h/G', '2026-08-31 20:05:22', 1, '2026-08-31 14:55:22'),
(8, 1, '$2y$10$EwbdSw4a9zwoSIA6GuYM0.2xo4dwWcC.qD/2YnyxxZOeO9rwp2uhm', '2026-09-01 00:23:16', 1, '2026-08-31 19:13:16');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tamanho`
--

CREATE TABLE `tamanho` (
  `id_tamanho` int(11) NOT NULL,
  `descricao` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tamanho`
--

INSERT INTO `tamanho` (`id_tamanho`, `descricao`) VALUES
(1, '36'),
(2, '37'),
(3, '38'),
(4, '39'),
(5, '40'),
(6, '41'),
(7, '42'),
(8, '43'),
(9, '44'),
(10, '45'),
(11, '46'),
(12, 'PP'),
(13, 'P'),
(14, 'M'),
(15, 'G'),
(16, 'GG'),
(17, 'XG'),
(18, 'Único');

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
  `foto_perfil` varchar(255) DEFAULT NULL,
  `numero` varchar(10) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nome`, `cpf`, `email`, `telefone`, `senha`, `tipo`, `cep`, `foto_perfil`, `numero`, `data_cadastro`) VALUES
(1, 'Davi Waitman', NULL, 'davi.waitman@gmail.com', '(16) 99999-9999', '$2y$10$HIRiMhwjWtN7Efss3QI29uV/fBYsuwR5upKXYo0j/oZMQ/YW.uDzG', 'cliente', NULL, NULL, NULL, '2026-09-11 08:46:31'),
(8, 'Administrador', '123.456.789-00', 'admin@gmail.com', '(16) 99999-9999', '$2y$10$yqLHGXs607j.bmL7nYhx8.y6A7iBkKDl.XAOdCwTgPIve7mO5iN7i', 'admin', '15900-000', NULL, NULL, '2026-09-11 08:46:31'),
(9, 'Gabriel Gallo Morita', '697.844.831-14', 'gabrielgallomorita2009@gmail.com', '(16) 98828-3282', '$2y$10$3zFdTSxqaIzBO5c7nBIRp.GzU6lJ7aP5uwKWbwuzTeVTVHj/6cF3.', 'cliente', '15902222', NULL, '2', '2026-10-17 12:12:15');

--
-- Acionadores `usuario`
--
DELIMITER $$
CREATE TRIGGER `usuario_definir_data_cadastro` BEFORE INSERT ON `usuario` FOR EACH ROW SET NEW.data_cadastro = COALESCE(NEW.data_cadastro, NOW())
$$
DELIMITER ;

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
-- Índices de tabela `produto_tamanho`
--
ALTER TABLE `produto_tamanho`
  ADD PRIMARY KEY (`id_produto`,`id_tamanho`),
  ADD KEY `id_tamanho` (`id_tamanho`);

--
-- Índices de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_recuperacao_usuario` (`id_usuario`);

--
-- Índices de tabela `tamanho`
--
ALTER TABLE `tamanho`
  ADD PRIMARY KEY (`id_tamanho`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `entrega`
--
ALTER TABLE `entrega`
  MODIFY `id_entrega` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `item_pedido`
--
ALTER TABLE `item_pedido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `pedido`
--
ALTER TABLE `pedido`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `produto`
--
ALTER TABLE `produto`
  MODIFY `id_produto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `tamanho`
--
ALTER TABLE `tamanho`
  MODIFY `id_tamanho` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
-- Restrições para tabelas `produto_tamanho`
--
ALTER TABLE `produto_tamanho`
  ADD CONSTRAINT `produto_tamanho_ibfk_1` FOREIGN KEY (`id_produto`) REFERENCES `produto` (`id_produto`),
  ADD CONSTRAINT `produto_tamanho_ibfk_2` FOREIGN KEY (`id_tamanho`) REFERENCES `tamanho` (`id_tamanho`);

--
-- Restrições para tabelas `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD CONSTRAINT `fk_recuperacao_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
