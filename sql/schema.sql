CREATE DATABASE knight CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE knight;

-- knight.clientes definition

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) DEFAULT NULL,
  `bilhetagem` tinyint(1) DEFAULT 0,
  `qtd_bilhetagem` int(11) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- knight.modalidades definition

CREATE TABLE `modalidades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sigla` varchar(20) DEFAULT NULL,
  `nome` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sigla` (`sigla`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- knight.usuarios definition

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `tipo` enum('financeiro','infra','master') DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- knight.agendamentos_checagem definition

CREATE TABLE `agendamentos_checagem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dia_semana` enum('segunda','terca','quarta','quinta','sexta','sabado','domingo') DEFAULT NULL,
  `dia_mes` int(2) DEFAULT NULL COMMENT 'Dia fixo do mês (1-31)',
  `primeira_semana` tinyint(1) DEFAULT 0 COMMENT 'Agendar na primeira semana do mês',
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `hora_execucao` time DEFAULT '00:00:00',
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `criado_por` int(11) DEFAULT NULL,
  `todos_clientes` tinyint(1) DEFAULT 1 COMMENT '1 para todos clientes, 0 para seleção específica',
  `clientes_ids` text DEFAULT NULL COMMENT 'IDs dos clientes separados por vírgula quando todos_clientes=0',
  `ultima_execucao` datetime DEFAULT NULL COMMENT 'Data/hora da última execução',
  `proxima_execucao` datetime DEFAULT NULL COMMENT 'Data/hora da próxima execução calculada',
  PRIMARY KEY (`id`),
  KEY `criado_por` (`criado_por`),
  CONSTRAINT `agendamentos_checagem_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- knight.checagens definition

CREATE TABLE `checagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) DEFAULT NULL,
  `data` datetime DEFAULT NULL,
  `resumo` varchar(255) DEFAULT NULL,
  `status` enum('sucesso','erro','aviso') DEFAULT NULL,
  `resultado_json` longtext DEFAULT NULL,
  `caminho_arquivo` text DEFAULT NULL,
  `tipo_checagem` enum('cadastrados','completa') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `checagens_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- knight.clientes_modalidades definition

CREATE TABLE `clientes_modalidades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) DEFAULT NULL,
  `modalidade_id` int(11) DEFAULT NULL,
  `quantidade` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `modalidade_id` (`modalidade_id`),
  CONSTRAINT `clientes_modalidades_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `clientes_modalidades_ibfk_2` FOREIGN KEY (`modalidade_id`) REFERENCES `modalidades` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- knight.conexoes definition

CREATE TABLE `conexoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) DEFAULT NULL,
  `ipv6` varchar(45) DEFAULT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `tipo_banco` enum('mysql','postgres') DEFAULT NULL,
  `versao_infra` enum('legado','atual') DEFAULT NULL,
  `dbname` varchar(100) NOT NULL DEFAULT 'pacsdb',
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `conexoes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- criar admin
insert into usuarios (nome,email,senha,tipo,ativo)values('admin','admin@admin.co','$2y$10$QeC6IsKkuf7Ho/GZZo4MdeBJEiesUQmMeYdUicUeec0yUoxOoJhOS','master',true);