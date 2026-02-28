-- database.sql
CREATE DATABASE IF NOT EXISTS botzap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE botzap;
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS modelos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL
);
CREATE TABLE IF NOT EXISTS cores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL
);
CREATE TABLE IF NOT EXISTS imagens_salvas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caminho VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS mensagens_enviadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    cor VARCHAR(50) NOT NULL,
    tipo_imagem ENUM('url', 'local') NOT NULL,
    caminho_link VARCHAR(255) NOT NULL,
    texto_final TEXT NOT NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Insert Default Admin (Password: admin123)
-- Password hash generated using password_hash('admin123', PASSWORD_DEFAULT)
INSERT IGNORE INTO usuarios (username, password)
VALUES (
        'admin',
        '$2y$10$wOaE.Yc9d.A170NtoO61Dud24qEOM0fEDeW306L3J.J8b7Z0T0.1S'
    );
-- Insert Initial Data
INSERT IGNORE INTO modelos (nome)
VALUES ('iPhone 13 Pro Max'),
    ('iPhone 12'),
    ('Samsung S23 Ultra'),
    ('Xiaomi 13 Pro');
INSERT IGNORE INTO cores (nome)
VALUES ('Preto'),
    ('Branco'),
    ('Azul Sierra'),
    ('Grafite'),
    ('Ouro');