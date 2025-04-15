CREATE DATABASE knight CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE knight;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    senha VARCHAR(255),
    tipo ENUM('financeiro', 'infra', 'master'),
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE modalidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sigla VARCHAR(20) UNIQUE,
    nome VARCHAR(100)
);

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150),
    bilhetagem BOOLEAN DEFAULT FALSE,
    qtd_bilhetagem INT DEFAULT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clientes_modalidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    modalidade_id INT,
    quantidade INT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (modalidade_id) REFERENCES modalidades(id)
);

CREATE TABLE conexoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    ipv6 VARCHAR(45),
    usuario VARCHAR(100),
    senha VARCHAR(255),
    tipo_banco ENUM('mysql', 'postgres'),
    versao_infra ENUM('legado', 'atual'),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

CREATE TABLE checagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    data DATETIME,
    resultado_json TEXT,
    caminho_arquivo TEXT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);
