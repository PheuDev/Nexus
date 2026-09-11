-- ============================================================
-- NEXUS — SCHEMA MYSQL (version consolidée)
-- Exécuter une seule fois sur une base vierge.
-- Usage : mysql -u root -p < database/schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS nexus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nexus;

-- --------------------------------------------------------
-- Utilisateurs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    name                  VARCHAR(100)  NOT NULL,
    email                 VARCHAR(150)  NOT NULL UNIQUE,
    password_hash         VARCHAR(255)  NOT NULL,
    currency              VARCHAR(10)   NOT NULL DEFAULT 'EUR',
    theme                 VARCHAR(20)   NOT NULL DEFAULT 'dark',
    locale                VARCHAR(10)   NOT NULL DEFAULT 'fr',
    notifications_enabled TINYINT(1)   NOT NULL DEFAULT 1,
    created_at            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Tentatives de connexion (anti brute-force)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    email        VARCHAR(150) NOT NULL,
    attempted_at DATETIME     NOT NULL,
    INDEX idx_email_time (email, attempted_at)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Catégories (revenus / dépenses)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT          NOT NULL,
    name    VARCHAR(80)  NOT NULL,
    type    ENUM('income','expense') NOT NULL,
    color   VARCHAR(20)  DEFAULT '#00d9ff',
    icon    VARCHAR(40)  DEFAULT 'circle',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Transactions (revenus / dépenses)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS transactions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT            NOT NULL,
    category_id     INT            NULL,
    type            ENUM('income','expense') NOT NULL,
    amount          DECIMAL(12,2)  NOT NULL,
    label           VARCHAR(150)   NOT NULL,
    occurred_on     DATE           NOT NULL,
    recurrence      ENUM('none','daily','weekly','monthly','yearly') NOT NULL DEFAULT 'none',
    recurrence_end  DATE           NULL,
    note            TEXT           NULL,
    created_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_user_date (user_id, occurred_on)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Objectifs financiers
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS goals (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT            NOT NULL,
    title          VARCHAR(150)   NOT NULL,
    target_amount  DECIMAL(12,2)  NOT NULL,
    current_amount DECIMAL(12,2)  NOT NULL DEFAULT 0,
    target_date    DATE           NULL,
    status         ENUM('active','achieved') NOT NULL DEFAULT 'active',
    created_at     TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS goal_deposits (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    goal_id      INT           NOT NULL,
    amount       DECIMAL(12,2) NOT NULL,
    deposited_on DATE          NOT NULL,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Tâches / Planning
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS tasks (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    title      VARCHAR(200) NOT NULL,
    due_date   DATE         NULL,
    priority   ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    status     ENUM('pending','done')      NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Réinitialisation du mot de passe
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(150) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME     NOT NULL,
    used       TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token (token_hash)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Budgets mensuels par catégorie
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS budgets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT           NOT NULL,
    category_id INT           NOT NULL,
    amount      DECIMAL(12,2) NOT NULL,
    month       CHAR(7)       NOT NULL COMMENT 'Format YYYY-MM',
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_budget (user_id, category_id, month),
    FOREIGN KEY (user_id)     REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Journal d'activité
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    action     VARCHAR(50)  NOT NULL COMMENT 'create|update|delete|login|logout',
    entity     VARCHAR(50)  NOT NULL COMMENT 'transaction|goal|task|category|budget|user',
    entity_id  INT          NULL,
    summary    VARCHAR(255) NOT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_time (user_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
