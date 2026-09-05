-- phpMyAdmin SQL Dump (limpo — apenas estrutura, sem registros)
-- Baseado na estrutura mais recente de db_helios
-- Versão do servidor: 8.0.45

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
-- Estrutura para tabela `usuarios`
-- (criada antes das demais por ser referenciada pelas foreign keys)
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `empresa_id` int DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha_hash` text NOT NULL,
  `nivel_acesso` enum('admin','gerente','operador') NOT NULL DEFAULT 'operador'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `empresas`
--

CREATE TABLE `empresas` (
  `id` int NOT NULL,
  `nome_comercial` varchar(150) NOT NULL,
  `cnpj` varchar(20) NOT NULL,
  `codigo_acesso` varchar(50) NOT NULL,
  `data_adesao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `conversas`
--

CREATE TABLE `conversas` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_chat`
--

CREATE TABLE `historico_chat` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `conversa_id` int DEFAULT NULL,
  `pergunta_tecnica` text NOT NULL,
  `resposta_ia` text NOT NULL,
  `normas_relacionadas` varchar(255) DEFAULT NULL,
  `data_interacao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Índices para tabelas despejadas
--

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_usuarios_empresa` (`empresa_id`);

ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cnpj` (`cnpj`);

ALTER TABLE `conversas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_conversas_usuario` (`usuario_id`);

ALTER TABLE `historico_chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_historico_usuario` (`usuario_id`),
  ADD KEY `fk_historico_conversa` (`conversa_id`);

--
-- AUTO_INCREMENT para tabelas despejadas (todas zeradas, começando do 1)
--

ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `empresas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `conversas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `historico_chat`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Restrições para tabelas despejadas
--

ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

ALTER TABLE `conversas`
  ADD CONSTRAINT `fk_conversas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

ALTER TABLE `historico_chat`
  ADD CONSTRAINT `fk_historico_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_historico_conversa` FOREIGN KEY (`conversa_id`) REFERENCES `conversas` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;