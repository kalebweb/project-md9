-- AdminNeo 4.17.2 MySQL 8.0.35 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text,
  `cor` varchar(7) DEFAULT '#667eea',
  `ativo` tinyint(1) DEFAULT '1',
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_categorias_empresa` (`empresa_id`),
  CONSTRAINT `fk_categorias_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `categorias` (`id`, `empresa_id`, `nome`, `descricao`, `cor`, `ativo`, `data_criacao`) VALUES
(1,	1,	'Produtos',	'',	'#667eea',	1,	'2025-06-01 17:10:14'),
(2,	1,	'Tintas',	'Tintas em Geral',	'#eac066',	1,	'2025-06-01 17:31:34'),
(3,	1,	'Agro',	'',	'#1fd62b',	1,	'2025-06-01 17:48:00'),
(4,	2,	'Produtos',	'Produtos principais da empresa',	'#667eea',	1,	'2025-06-01 21:01:28'),
(5,	2,	'Serviços',	'Serviços oferecidos',	'#28a745',	1,	'2025-06-01 21:01:28'),
(6,	2,	'Consultoria',	'Serviços de consultoria',	'#ffc107',	1,	'2025-06-01 21:01:28'),
(7,	2,	'Outros',	'Outros produtos e serviços',	'#6c757d',	1,	'2025-06-01 21:01:28'),
(8,	3,	'Produtos',	'Produtos principais da empresa',	'#667eea',	1,	'2025-06-01 21:17:58'),
(9,	3,	'Serviços',	'Serviços oferecidos',	'#28a745',	1,	'2025-06-01 21:17:58'),
(10,	3,	'Consultoria',	'Serviços de consultoria',	'#ffc107',	1,	'2025-06-01 21:17:58'),
(11,	3,	'Outros',	'Outros produtos e serviços',	'#6c757d',	1,	'2025-06-01 21:17:58'),
(12,	1,	'Produtos',	'',	'#667eea',	1,	'2025-06-01 22:09:52');

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `razao_social` varchar(200) NOT NULL,
  `nome_fantasia` varchar(200) DEFAULT NULL,
  `cnpj` varchar(18) NOT NULL,
  `responsavel_nome` varchar(100) NOT NULL,
  `responsavel_cargo` varchar(100) DEFAULT NULL,
  `telefone_empresa` varchar(20) DEFAULT NULL,
  `telefone_responsavel` varchar(20) DEFAULT NULL,
  `email_empresa` varchar(100) DEFAULT NULL,
  `email_responsavel` varchar(100) NOT NULL,
  `endereco` text,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `observacoes` text,
  `ativo` tinyint(1) DEFAULT '1',
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_clientes_empresa` (`empresa_id`),
  KEY `idx_clientes_cnpj` (`cnpj`),
  CONSTRAINT `fk_clientes_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `clientes` (`id`, `empresa_id`, `razao_social`, `nome_fantasia`, `cnpj`, `responsavel_nome`, `responsavel_cargo`, `telefone_empresa`, `telefone_responsavel`, `email_empresa`, `email_responsavel`, `endereco`, `cidade`, `estado`, `cep`, `observacoes`, `ativo`, `data_criacao`, `data_atualizacao`) VALUES
(1,	1,	'Germano Saúde Animal',	'Germano',	'00000000000000',	'Roberto',	'Gerente',	'828282828282',	'72727272',	'empresa@cliente.com',	'roberto@cliente.com',	'Estrada Antiga do Mar',	'São Paulo',	'SP',	'04413000',	'Muito legal',	1,	'2025-06-01 16:03:16',	'2025-06-01 17:02:11');

DROP TABLE IF EXISTS `empresas`;
CREATE TABLE `empresas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `razao_social` varchar(200) NOT NULL,
  `nome_fantasia` varchar(200) DEFAULT NULL,
  `cnpj` varchar(18) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` text,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `plano` varchar(100) DEFAULT NULL,
  `limite_orcamentos` int DEFAULT '10',
  `orcamentos_utilizados` int DEFAULT '0',
  `data_vencimento` date DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cnpj` (`cnpj`),
  KEY `idx_empresas_cnpj` (`cnpj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `empresas` (`id`, `razao_social`, `nome_fantasia`, `cnpj`, `email`, `telefone`, `endereco`, `cidade`, `estado`, `cep`, `plano`, `limite_orcamentos`, `orcamentos_utilizados`, `data_vencimento`, `ativo`, `data_criacao`, `data_atualizacao`) VALUES
(1,	'JoyCode',	'KM Soluções em Marketing Estrategico e Comunicações LTDA',	'21941011000146',	'contato@kaleb.com.br',	'(62) 99302-6838',	'Rua 02 Quadra 02',	'Anápolis',	'GO',	'04413000',	'gratuito',	10,	0,	NULL,	1,	'2025-06-01 00:51:44',	'2025-06-01 17:46:05'),
(2,	'FOSPLAN',	'Fosplan',	'02799361000175',	'fosplan@fosplan.com.br',	'62 99302-6838',	'Rua 02 Quadra 02 Casa 11',	'Anápolis',	'GO',	'04413000',	'Free',	3,	0,	NULL,	0,	'2025-06-01 21:01:28',	'2025-06-01 21:50:13'),
(3,	'Cristal Vidros',	'CVL',	'01051143000195',	'cvl@gmail.com',	'(62) 99302-6838',	'Rua 02 Quadra 02 Casa 11',	'São Paulo',	'GO',	'04413000',	'Black Friday',	13,	0,	NULL,	1,	'2025-06-01 21:17:58',	'2025-06-01 21:31:21');

DELIMITER ;;

CREATE TRIGGER `tr_empresa_categorias_padrao` AFTER INSERT ON `empresas` FOR EACH ROW
BEGIN
    INSERT INTO categorias (empresa_id, nome, descricao, cor) VALUES
    (NEW.id, 'Produtos', 'Produtos principais da empresa', '#667eea'),
    (NEW.id, 'Serviços', 'Serviços oferecidos', '#28a745'),
    (NEW.id, 'Consultoria', 'Serviços de consultoria', '#ffc107'),
    (NEW.id, 'Outros', 'Outros produtos e serviços', '#6c757d');
END;;

DELIMITER ;

DROP TABLE IF EXISTS `logs_sistema`;
CREATE TABLE `logs_sistema` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `empresa_id` int DEFAULT NULL,
  `acao` varchar(100) NOT NULL,
  `tabela_afetada` varchar(50) DEFAULT NULL,
  `registro_id` int DEFAULT NULL,
  `dados_antigos` json DEFAULT NULL,
  `dados_novos` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_logs_usuario` (`usuario_id`),
  KEY `fk_logs_empresa` (`empresa_id`),
  CONSTRAINT `fk_logs_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_logs_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `logs_sistema` (`id`, `usuario_id`, `empresa_id`, `acao`, `tabela_afetada`, `registro_id`, `dados_antigos`, `dados_novos`, `ip_address`, `user_agent`, `data_criacao`) VALUES
(1,	1,	NULL,	'logout',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 00:55:01'),
(2,	2,	1,	'login',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 00:55:05'),
(3,	2,	1,	'logout',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 01:02:51'),
(4,	1,	NULL,	'login',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 01:02:56'),
(5,	1,	NULL,	'logout',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 01:03:47'),
(6,	2,	1,	'login',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 01:03:55'),
(7,	2,	1,	'adicionar_colaborador',	'usuarios',	3,	NULL,	'{\"nome\": \"MARIA\", \"tipo\": \"colaborador\", \"email\": \"maria@kaleb.com.br\", \"senha\": \"2h2anidra433\"}',	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 01:24:43'),
(8,	2,	1,	'logout',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 01:24:56'),
(9,	3,	1,	'login',	'usuarios',	3,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 01:25:04'),
(10,	3,	1,	'logout',	'usuarios',	3,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 01:25:35'),
(11,	1,	NULL,	'login',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 01:25:41'),
(12,	1,	NULL,	'logout',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 01:25:52'),
(13,	1,	NULL,	'login',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 01:29:32'),
(14,	1,	NULL,	'logout',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 01:54:29'),
(15,	1,	NULL,	'login',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 01:54:38'),
(16,	1,	NULL,	'logout',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 01:54:44'),
(17,	2,	1,	'login',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 01:54:49'),
(18,	2,	1,	'logout',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 02:48:37'),
(19,	2,	1,	'login',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 02:48:40'),
(20,	2,	1,	'logout',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 02:48:56'),
(21,	3,	1,	'login',	'usuarios',	3,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 02:49:00'),
(22,	3,	1,	'logout',	'usuarios',	3,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 02:49:05'),
(23,	1,	NULL,	'login',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 02:49:09'),
(24,	1,	NULL,	'logout',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 02:49:22'),
(25,	2,	1,	'login',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 02:49:30'),
(26,	2,	1,	'logout',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 03:10:23'),
(27,	1,	NULL,	'login',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 03:10:27'),
(28,	1,	NULL,	'logout',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 03:10:52'),
(29,	2,	1,	'login',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 03:10:56'),
(30,	2,	1,	'logout',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 03:50:46'),
(31,	2,	1,	'login',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 03:50:49'),
(32,	1,	NULL,	'login',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 15:59:53'),
(33,	1,	NULL,	'logout',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 15:59:57'),
(34,	2,	1,	'login',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 16:00:02'),
(35,	2,	1,	'editar_cliente',	'clientes',	1,	'{\"id\": 1, \"cep\": \"04413000\", \"cnpj\": \"00000000000000\", \"ativo\": null, \"cidade\": \"São Paulo\", \"estado\": \"SP\", \"endereco\": \"Estrada Antiga do Mar\", \"empresa_id\": 1, \"observacoes\": \"Muito legal\", \"data_criacao\": \"2025-06-01 13:03:16\", \"razao_social\": \"CLIENTE X\", \"email_empresa\": \"empresa@cliente.com\", \"nome_fantasia\": \"NOME FANTASIA\", \"data_atualizacao\": \"2025-06-01 13:03:16\", \"responsavel_nome\": \"Roberto\", \"telefone_empresa\": \"828282828282\", \"email_responsavel\": \"roberto@cliente.com\", \"responsavel_cargo\": \"Gerente\", \"telefone_responsavel\": \"72727272\"}',	'{\"cep\": \"04413000\", \"cnpj\": \"00000000000000\", \"ativo\": \"1\", \"cidade\": \"São Paulo\", \"estado\": \"SP\", \"endereco\": \"Estrada Antiga do Mar\", \"observacoes\": \"Muito legal\", \"razao_social\": \"CLIENTE XYYY\", \"email_empresa\": \"empresa@cliente.com\", \"nome_fantasia\": \"NOME FANTASIA\", \"responsavel_nome\": \"Roberto\", \"telefone_empresa\": \"828282828282\", \"email_responsavel\": \"roberto@cliente.com\", \"responsavel_cargo\": \"Gerente\", \"telefone_responsavel\": \"72727272\"}',	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 16:20:39'),
(36,	2,	1,	'editar_cliente',	'clientes',	1,	'{\"id\": 1, \"cep\": \"04413000\", \"cnpj\": \"00000000000000\", \"ativo\": 1, \"cidade\": \"São Paulo\", \"estado\": \"SP\", \"endereco\": \"Estrada Antiga do Mar\", \"empresa_id\": 1, \"observacoes\": \"Muito legal\", \"data_criacao\": \"2025-06-01 13:03:16\", \"razao_social\": \"CLIENTE XYYY\", \"email_empresa\": \"empresa@cliente.com\", \"nome_fantasia\": \"NOME FANTASIA\", \"data_atualizacao\": \"2025-06-01 13:20:39\", \"responsavel_nome\": \"Roberto\", \"telefone_empresa\": \"828282828282\", \"email_responsavel\": \"roberto@cliente.com\", \"responsavel_cargo\": \"Gerente\", \"telefone_responsavel\": \"72727272\"}',	'{\"cep\": \"04413000\", \"cnpj\": \"00000000000000\", \"ativo\": \"1\", \"cidade\": \"São Paulo\", \"estado\": \"SP\", \"endereco\": \"Estrada Antiga do Mar\", \"observacoes\": \"Muito legal\", \"razao_social\": \"CLIENTE XYYY 0002\", \"email_empresa\": \"empresa@cliente.com\", \"nome_fantasia\": \"NOME FANTASIA\", \"responsavel_nome\": \"Roberto\", \"telefone_empresa\": \"828282828282\", \"email_responsavel\": \"roberto@cliente.com\", \"responsavel_cargo\": \"Gerente\", \"telefone_responsavel\": \"72727272\"}',	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 16:20:56'),
(37,	2,	1,	'editar_cliente',	'clientes',	1,	'{\"id\": 1, \"cep\": \"04413000\", \"cnpj\": \"00000000000000\", \"ativo\": 1, \"cidade\": \"São Paulo\", \"estado\": \"SP\", \"endereco\": \"Estrada Antiga do Mar\", \"empresa_id\": 1, \"observacoes\": \"Muito legal\", \"data_criacao\": \"2025-06-01 13:03:16\", \"razao_social\": \"CLIENTE XYYY 0002\", \"email_empresa\": \"empresa@cliente.com\", \"nome_fantasia\": \"NOME FANTASIA\", \"data_atualizacao\": \"2025-06-01 13:20:56\", \"responsavel_nome\": \"Roberto\", \"telefone_empresa\": \"828282828282\", \"email_responsavel\": \"roberto@cliente.com\", \"responsavel_cargo\": \"Gerente\", \"telefone_responsavel\": \"72727272\"}',	'{\"cep\": \"04413000\", \"cnpj\": \"00000000000000\", \"ativo\": \"1\", \"cidade\": \"São Paulo\", \"estado\": \"SP\", \"endereco\": \"Estrada Antiga do Mar\", \"observacoes\": \"Muito legal\", \"razao_social\": \"CLIENTE\", \"email_empresa\": \"empresa@cliente.com\", \"nome_fantasia\": \"NOME FANTASIA\", \"responsavel_nome\": \"Roberto\", \"telefone_empresa\": \"828282828282\", \"email_responsavel\": \"roberto@cliente.com\", \"responsavel_cargo\": \"Gerente\", \"telefone_responsavel\": \"72727272\"}',	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 16:21:03'),
(38,	2,	1,	'editar_cliente',	'clientes',	1,	'{\"id\": 1, \"cep\": \"04413000\", \"cnpj\": \"00000000000000\", \"ativo\": 1, \"cidade\": \"São Paulo\", \"estado\": \"SP\", \"endereco\": \"Estrada Antiga do Mar\", \"empresa_id\": 1, \"observacoes\": \"Muito legal\", \"data_criacao\": \"2025-06-01 13:03:16\", \"razao_social\": \"CLIENTE\", \"email_empresa\": \"empresa@cliente.com\", \"nome_fantasia\": \"NOME FANTASIA\", \"data_atualizacao\": \"2025-06-01 13:21:03\", \"responsavel_nome\": \"Roberto\", \"telefone_empresa\": \"828282828282\", \"email_responsavel\": \"roberto@cliente.com\", \"responsavel_cargo\": \"Gerente\", \"telefone_responsavel\": \"72727272\"}',	'{\"cep\": \"04413000\", \"cnpj\": \"00000000000000\", \"ativo\": \"1\", \"cidade\": \"São Paulo\", \"estado\": \"SP\", \"endereco\": \"Estrada Antiga do Mar\", \"observacoes\": \"Muito legal\", \"razao_social\": \"Germano Saúde Animal\", \"email_empresa\": \"empresa@cliente.com\", \"nome_fantasia\": \"NOME FANTASIA\", \"responsavel_nome\": \"Roberto\", \"telefone_empresa\": \"828282828282\", \"email_responsavel\": \"roberto@cliente.com\", \"responsavel_cargo\": \"Gerente\", \"telefone_responsavel\": \"72727272\"}',	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 16:59:37'),
(39,	2,	1,	'editar_cliente',	'clientes',	1,	'{\"id\": 1, \"cep\": \"04413000\", \"cnpj\": \"00000000000000\", \"ativo\": 1, \"cidade\": \"São Paulo\", \"estado\": \"SP\", \"endereco\": \"Estrada Antiga do Mar\", \"empresa_id\": 1, \"observacoes\": \"Muito legal\", \"data_criacao\": \"2025-06-01 13:03:16\", \"razao_social\": \"Germano Saúde Animal\", \"email_empresa\": \"empresa@cliente.com\", \"nome_fantasia\": \"NOME FANTASIA\", \"data_atualizacao\": \"2025-06-01 13:59:37\", \"responsavel_nome\": \"Roberto\", \"telefone_empresa\": \"828282828282\", \"email_responsavel\": \"roberto@cliente.com\", \"responsavel_cargo\": \"Gerente\", \"telefone_responsavel\": \"72727272\"}',	'{\"cep\": \"04413000\", \"cnpj\": \"00000000000000\", \"ativo\": \"1\", \"cidade\": \"São Paulo\", \"estado\": \"SP\", \"endereco\": \"Estrada Antiga do Mar\", \"observacoes\": \"Muito legal\", \"razao_social\": \"Germano Saúde Animal\", \"email_empresa\": \"empresa@cliente.com\", \"nome_fantasia\": \"Germano\", \"responsavel_nome\": \"Roberto\", \"telefone_empresa\": \"828282828282\", \"email_responsavel\": \"roberto@cliente.com\", \"responsavel_cargo\": \"Gerente\", \"telefone_responsavel\": \"72727272\"}',	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 17:02:11'),
(40,	2,	1,	'logout',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 17:39:56'),
(41,	2,	1,	'login',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 17:39:58'),
(42,	2,	1,	'logout',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 17:46:33'),
(43,	1,	NULL,	'login',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 17:46:36'),
(44,	1,	NULL,	'logout',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 17:47:02'),
(45,	2,	1,	'login',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 17:47:05'),
(46,	2,	1,	'logout',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 17:47:09'),
(47,	3,	1,	'login',	'usuarios',	3,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 17:47:12'),
(48,	3,	1,	'logout',	'usuarios',	3,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 20:55:49'),
(49,	1,	NULL,	'login',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 20:55:53'),
(50,	1,	NULL,	'logout',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 21:13:24'),
(51,	1,	NULL,	'login',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 21:13:27'),
(52,	1,	NULL,	'logout',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 21:26:36'),
(53,	1,	NULL,	'login',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 21:26:38'),
(54,	1,	NULL,	'logout',	'usuarios',	1,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 21:55:20'),
(55,	2,	1,	'login',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 21:55:45'),
(56,	2,	1,	'logout',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 22:07:08'),
(57,	2,	1,	'login',	'usuarios',	2,	NULL,	NULL,	'::1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',	'2025-06-01 22:07:12');

DROP TABLE IF EXISTS `orcamento_itens`;
CREATE TABLE `orcamento_itens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orcamento_id` int NOT NULL,
  `produto_id` int DEFAULT NULL,
  `descricao` varchar(500) NOT NULL,
  `quantidade` decimal(10,3) DEFAULT '1.000',
  `valor_unitario` decimal(10,2) NOT NULL,
  `valor_promocional` decimal(10,2) DEFAULT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `valor_final` decimal(10,2) DEFAULT NULL,
  `ordem` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_itens_orcamento` (`orcamento_id`),
  CONSTRAINT `fk_itens_orcamento` FOREIGN KEY (`orcamento_id`) REFERENCES `orcamentos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DELIMITER ;;

CREATE TRIGGER `tr_item_insert_update` AFTER INSERT ON `orcamento_itens` FOR EACH ROW
BEGIN
    UPDATE orcamentos 
    SET valor_total = (
        SELECT COALESCE(SUM(valor_total), 0) 
        FROM orcamento_itens 
        WHERE orcamento_id = NEW.orcamento_id
    ) 
    WHERE id = NEW.orcamento_id;
END;;

CREATE TRIGGER `tr_item_insert` AFTER INSERT ON `orcamento_itens` FOR EACH ROW
BEGIN
    DECLARE v_valor_final DECIMAL(10,2);
    
    -- Calcular valor total dos itens
    SELECT COALESCE(SUM(valor_total), 0) INTO v_valor_final
    FROM orcamento_itens 
    WHERE orcamento_id = NEW.orcamento_id;
    
    -- Atualizar orçamento
    UPDATE orcamentos 
    SET valor_total = v_valor_final,
        valor_final = v_valor_final - COALESCE(valor_desconto, 0)
    WHERE id = NEW.orcamento_id;
END;;

CREATE TRIGGER `tr_item_update` AFTER UPDATE ON `orcamento_itens` FOR EACH ROW
BEGIN
    UPDATE orcamentos 
    SET valor_total = (
        SELECT COALESCE(SUM(valor_total), 0) 
        FROM orcamento_itens 
        WHERE orcamento_id = NEW.orcamento_id
    ) 
    WHERE id = NEW.orcamento_id;
END;;

CREATE TRIGGER `tr_item_delete` AFTER DELETE ON `orcamento_itens` FOR EACH ROW
BEGIN
    UPDATE orcamentos 
    SET valor_total = (
        SELECT COALESCE(SUM(valor_total), 0) 
        FROM orcamento_itens 
        WHERE orcamento_id = OLD.orcamento_id
    ) 
    WHERE id = OLD.orcamento_id;
END;;

DELIMITER ;

DROP TABLE IF EXISTS `orcamentos`;
CREATE TABLE `orcamentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `cliente_id` int DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `colaborador_id` int NOT NULL,
  `numero_orcamento` varchar(50) NOT NULL,
  `cliente_email` varchar(100) DEFAULT NULL,
  `cliente_telefone` varchar(20) DEFAULT NULL,
  `cliente_endereco` text,
  `descricao` text,
  `usuario_id` int DEFAULT NULL,
  `valor_total` decimal(10,2) DEFAULT '0.00',
  `status` varchar(20) DEFAULT NULL,
  `validade` date DEFAULT NULL,
  `observacoes` text,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_numero_empresa` (`numero_orcamento`,`empresa_id`),
  KEY `idx_orcamentos_empresa` (`empresa_id`),
  KEY `idx_orcamentos_colaborador` (`colaborador_id`),
  KEY `idx_orcamentos_status` (`status`),
  KEY `idx_orcamentos_data` (`data_criacao`),
  CONSTRAINT `fk_orcamentos_colaborador` FOREIGN KEY (`colaborador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orcamentos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DELIMITER ;;

CREATE TRIGGER `tr_orcamento_insert` AFTER INSERT ON `orcamentos` FOR EACH ROW
BEGIN
    UPDATE empresas 
    SET orcamentos_utilizados = orcamentos_utilizados + 1 
    WHERE id = NEW.empresa_id;
END;;

CREATE TRIGGER `tr_orcamento_delete` AFTER DELETE ON `orcamentos` FOR EACH ROW
BEGIN
    UPDATE empresas 
    SET orcamentos_utilizados = GREATEST(0, orcamentos_utilizados - 1) 
    WHERE id = OLD.empresa_id;
END;;

DELIMITER ;

DROP TABLE IF EXISTS `pagamentos`;
CREATE TABLE `pagamentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_pagamento` date NOT NULL,
  `data_vencimento` date NOT NULL,
  `status` enum('pendente','aprovado','cancelado') DEFAULT 'pendente',
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `referencia_pagamento` varchar(100) DEFAULT NULL,
  `observacoes` text,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pagamentos_empresa` (`empresa_id`),
  KEY `idx_pagamentos_vencimento` (`data_vencimento`),
  CONSTRAINT `fk_pagamentos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `planos`;
CREATE TABLE `planos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text,
  `valor` decimal(10,2) NOT NULL DEFAULT '0.00',
  `limite_orcamentos` int NOT NULL DEFAULT '100',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `planos` (`id`, `nome`, `descricao`, `valor`, `limite_orcamentos`, `criado_em`) VALUES
(1,	'Black Friday',	'Corra pq vai acabar!',	97.00,	13,	'2025-06-01 18:28:36'),
(2,	'Light',	'',	5.00,	5,	'2025-06-01 18:41:09'),
(3,	'Free',	'',	0.00,	3,	'2025-06-01 18:50:04');

DROP TABLE IF EXISTS `produtos`;
CREATE TABLE `produtos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `categoria_id` int DEFAULT NULL,
  `nome` varchar(200) NOT NULL,
  `descricao` text,
  `preco` decimal(10,2) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `unidade` varchar(20) DEFAULT 'UN',
  `estoque_minimo` int DEFAULT '0',
  `estoque_atual` int DEFAULT '0',
  `ativo` tinyint(1) DEFAULT '1',
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_produtos_empresa` (`empresa_id`),
  KEY `idx_produtos_categoria` (`categoria_id`),
  CONSTRAINT `fk_produtos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_produtos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `produtos` (`id`, `empresa_id`, `categoria_id`, `nome`, `descricao`, `preco`, `foto`, `codigo`, `unidade`, `estoque_minimo`, `estoque_atual`, `ativo`, `data_criacao`, `data_atualizacao`) VALUES
(1,	1,	2,	'DECORA ACRÍLICO PREMIUM SEDA 18L',	'Legal',	997.00,	'683c897651c00_1748797814.jpg',	'02',	'L',	5,	10,	1,	'2025-06-01 17:10:14',	'2025-06-01 20:55:27'),
(2,	1,	12,	'Confiplan Núcleo Premium K',	'Confiplan Núcleo Premium K',	100.00,	'683ccfb0e8d1b_1748815792.jpg',	'1',	'KG',	5,	10,	1,	'2025-06-01 22:09:52',	'2025-06-01 22:09:52');

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('super_admin','admin_empresa','colaborador') NOT NULL,
  `empresa_id` int DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_usuarios_email` (`email`),
  KEY `idx_usuarios_empresa` (`empresa_id`),
  CONSTRAINT `fk_usuarios_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo`, `empresa_id`, `ativo`, `data_criacao`, `data_atualizacao`) VALUES
(1,	'Super Admin',	'admin@sistema.com',	'$2y$10$ygolJIiVuIX024YfRiG9A.W8skjZ9mTWOFilC5BVgSttDNnsTE5Zu',	'super_admin',	NULL,	1,	'2025-06-01 00:45:51',	'2025-06-01 00:53:09'),
(2,	'KALEB MARTINS',	'contato@kaleb.com.br',	'$2y$10$ygolJIiVuIX024YfRiG9A.W8skjZ9mTWOFilC5BVgSttDNnsTE5Zu',	'admin_empresa',	1,	1,	'2025-06-01 00:51:44',	'2025-06-01 00:51:44'),
(3,	'MARIA',	'maria@kaleb.com.br',	'$2y$10$cEWNqNmLm5IPryfGWHOmVuhMMl76yjxec1Yeb.zczwnwHGkHFXXT2',	'colaborador',	1,	1,	'2025-06-01 01:24:43',	'2025-06-01 01:24:43'),
(5,	'Maurício',	'mau@fosplan.com.br',	'$2y$10$qX5J2kJy/zy876fRTG1jH.0F9S5OBAfelQQE2Pn7UbU1CkdLR/pUG',	'colaborador',	2,	1,	'2025-06-01 21:09:18',	'2025-06-01 21:09:18'),
(6,	'Vendas',	'vendas@cvl.com.br',	'$2y$10$px.OLId6JLZV8ECNbXcLnOehSvQSy/rhuYxhAlCdr46gc1QcsP9cO',	'admin_empresa',	3,	1,	'2025-06-01 21:18:35',	'2025-06-01 21:18:35');

-- 2025-06-01 22:31:40 UTC
