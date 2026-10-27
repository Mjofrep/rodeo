CREATE DATABASE IF NOT EXISTS rodeo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rodeo;

CREATE TABLE IF NOT EXISTS jueces (
    id TINYINT UNSIGNED PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre_completo VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rodeo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judge_id TINYINT UNSIGNED NOT NULL,
    team_id TINYINT UNSIGNED NOT NULL,
    competition_id TINYINT UNSIGNED NOT NULL,
    status ENUM('draft', 'suspended', 'completed') NOT NULL DEFAULT 'draft',
    scores JSON NOT NULL,
    deductions DECIMAL(6,2) NOT NULL DEFAULT 0,
    observations TEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY unique_evaluation (judge_id, team_id, competition_id)
) ENGINE=InnoDB;
