-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mysql:3306
-- Tempo de geração: 11/06/2026 às 01:42
-- Versão do servidor: 8.0.45
-- Versão do PHP: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `db_helios`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `empresas`
--

CREATE TABLE `empresas` (
  `id` int NOT NULL,
  `nome_comercial` varchar(150) NOT NULL,
  `cnpj` varchar(20) NOT NULL,
  `data_adesao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `empresas`
--

INSERT INTO `empresas` (`id`, `nome_comercial`, `cnpj`, `data_adesao`) VALUES
(1, 'helios', '1212121212122', '2026-05-30 15:01:01'),
(2, 'marvel', '12324234234234', '2026-05-30 15:03:21');

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_chat`
--

CREATE TABLE `historico_chat` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `pergunta_tecnica` text NOT NULL,
  `resposta_ia` text NOT NULL,
  `normas_relacionadas` varchar(255) DEFAULT NULL,
  `data_interacao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `telemetria`
--

CREATE TABLE `telemetria` (
  `id` bigint NOT NULL,
  `usina_id` int NOT NULL,
  `parametro` varchar(50) NOT NULL,
  `valor_leitura` decimal(15,4) NOT NULL,
  `data_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usinas`
--

CREATE TABLE `usinas` (
  `id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `nome_usina` varchar(100) NOT NULL,
  `tipo_geracao` enum('solar','eolica','hidro','termica','biomassa') NOT NULL,
  `capacidade_mw` decimal(10,2) DEFAULT NULL,
  `localizacao_cidade` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `empresa_id` int DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha_hash` text NOT NULL,
  `nivel_acesso` enum('admin','gerente','operador') NOT NULL DEFAULT 'operador'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `empresa_id`, `nome`, `email`, `senha_hash`, `nivel_acesso`) VALUES
(2, 2, 'Samuel Antunes de Oliveira Gomes', 'samuelantuneso@icloud.com', '$2y$10$NM0rR45AMU98CEU2HIZ/OO1gUg6XrpHi52ddjHbh/E6CfaVm299uG', 'admin');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cnpj` (`cnpj`);

--
-- Índices de tabela `historico_chat`
--
ALTER TABLE `historico_chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_historico_usuario` (`usuario_id`);

--
-- Índices de tabela `telemetria`
--
ALTER TABLE `telemetria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_telemetria_usina_hora` (`usina_id`,`data_hora`),
  ADD KEY `idx_telemetria_parametro` (`parametro`);

--
-- Índices de tabela `usinas`
--
ALTER TABLE `usinas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_usinas_empresa` (`empresa_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_usuarios_empresa` (`empresa_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `historico_chat`
--
ALTER TABLE `historico_chat`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `telemetria`
--
ALTER TABLE `telemetria`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usinas`
--
ALTER TABLE `usinas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `historico_chat`
--
ALTER TABLE `historico_chat`
  ADD CONSTRAINT `fk_historico_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `telemetria`
--
ALTER TABLE `telemetria`
  ADD CONSTRAINT `fk_telemetria_usina` FOREIGN KEY (`usina_id`) REFERENCES `usinas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `usinas`
--
ALTER TABLE `usinas`
  ADD CONSTRAINT `fk_usinas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

