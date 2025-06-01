-- Script de estrutura do banco de dados para o sistema de orçamentos

CREATE TABLE IF NOT EXISTS `orcamentos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT NOT NULL,
  `cliente_id` INT NULL,
  `colaborador_id` INT NULL,
  `numero_orcamento` VARCHAR(32) NULL,
  `titulo` VARCHAR(255) NULL,
  `descricao` TEXT NULL,
  `validade` DATE NULL,
  `observacoes` TEXT NULL,
  `condicoes_pagamento` VARCHAR(255) NULL,
  `prazo_entrega` VARCHAR(255) NULL,
  `status` VARCHAR(20) NULL,
  `valor_desconto` DECIMAL(10,2) NULL,
  `valor_total` DECIMAL(10,2) NULL,
  `valor_final` DECIMAL(10,2) NULL,
  `usuario_id` INT NULL,
  `data_criacao` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orcamento_itens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `orcamento_id` INT NOT NULL,
  `produto_id` INT NULL,
  `descricao` TEXT NULL,
  `quantidade` INT NOT NULL DEFAULT 1,
  `valor_unitario` DECIMAL(10,2) NULL,
  `valor_promocional` DECIMAL(10,2) NULL,
  `valor_total` DECIMAL(10,2) NULL,
  `ordem` INT NULL,
  FOREIGN KEY (`orcamento_id`) REFERENCES `orcamentos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Outras tabelas necessárias podem ser adicionadas conforme o sistema evoluir.
