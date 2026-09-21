-- ==========================================================
-- Skema Database: data_portfolio
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `data_portfolio`
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `data_portfolio`;

-- ----------------------------------------------------------
-- 1. Tabel: users
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. Tabel: categories
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. Tabel: projects
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `image` VARCHAR(255) NULL,
    `project_url` VARCHAR(255) NULL,
    `github_url` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_projects_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. Tabel: messages
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `subject` VARCHAR(200) NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- SEED DATA (Data Awal)
-- ==========================================================

-- 1. Akun Admin Default
-- Username: admin
-- Password: Mamasayang14
-- Hash di bawah dihasilkan menggunakan password_hash('Mamasayang14', PASSWORD_DEFAULT)
INSERT INTO `users` (`username`, `password`, `email`) VALUES
('admin', '$2y$10$xdWHFl/OsVCprF2m0HuCFeZlJ4XT6jouEOTlnMQkirK1dE9L78ZEW', 'admin@example.com')
ON DUPLICATE KEY UPDATE `password`=VALUES(`password`);

-- 2. Contoh Kategori Proyek
INSERT INTO `categories` (`name`, `slug`) VALUES
('Web Development', 'web-development'),
('Mobile Apps', 'mobile-apps'),
('UI/UX Design', 'ui-ux-design'),
('Data Science', 'data-science')
ON DUPLICATE KEY UPDATE `id`=`id`;
