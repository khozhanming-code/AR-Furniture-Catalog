-- =====================================================================
-- AR Furniture Catalog & AR Product Viewer
-- Database Schema + Sample Data
-- Import this file into MySQL/MariaDB (e.g. via phpMyAdmin or CLI):
--   mysql -u root -p ar_furniture < sql/database.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `ar_furniture`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ar_furniture`;

-- ---------------------------------------------------------------------
-- Table: admins
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(60) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin account: username = admin
-- The password_hash below is NOT a usable password — it is a
-- correctly-formatted bcrypt placeholder only, so the column constraints
-- are satisfied on import. You MUST generate your own hash and replace
-- it before you can log in. From a terminal:
--   php -r "echo password_hash('YourNewPassword', PASSWORD_DEFAULT);"
-- Then run:
--   UPDATE admins SET password_hash = 'PASTE_THE_HASH_HERE' WHERE username = 'admin';
-- (Full instructions are also in README.md, Section 3.)
INSERT INTO `admins` (`username`, `password_hash`) VALUES
('admin', '$2y$10$abcdefghijklmnopqrstuuXPLACEHOLDERHASHNOTUSABLEXX123456');

-- ---------------------------------------------------------------------
-- Table: categories
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(120) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`slug`, `name`, `sort_order`, `is_active`) VALUES
('chairs',  'Chairs',  1, 1),
('sofas',   'Sofas',   2, 1),
('tables',  'Tables',  3, 1),
('beds',    'Beds',    4, 1),
('storage', 'Storage', 5, 1),
('lighting','Lighting',6, 1);

-- ---------------------------------------------------------------------
-- Table: products
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NULL,
  `slug` VARCHAR(160) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(8) NOT NULL DEFAULT 'RM',
  `glb_path` VARCHAR(255) NULL,
  `usdz_path` VARCHAR(255) NULL,
  `thumb_path` VARCHAR(255) NULL,
  `width_cm` DECIMAL(8,2) NULL,
  `height_cm` DECIMAL(8,2) NULL,
  `depth_cm` DECIMAL(8,2) NULL,
  `glb_x_m` DECIMAL(8,4) NULL,
  `glb_y_m` DECIMAL(8,4) NULL,
  `glb_z_m` DECIMAL(8,4) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_active_sort` (`is_active`, `sort_order`),
  CONSTRAINT `fk_products_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Sample data
-- The armchair includes an original demo GLB and thumbnail.
-- Upload models for the other products from the admin panel.
-- ---------------------------------------------------------------------
INSERT INTO `products`
(`category_id`, `slug`, `name`, `description`, `price`, `currency`,
 `glb_path`, `usdz_path`, `thumb_path`,
 `width_cm`, `height_cm`, `depth_cm`, `is_active`, `sort_order`)
VALUES
(1, 'modern-armchair', 'Modern Armchair',
 'A comfortable upholstered armchair with a solid wood frame, perfect for a reading corner or living room.',
 899.00, 'RM', 'models/demo-armchair.glb', NULL, 'uploads/thumbs/demo-armchair.jpg', 74.00, 82.00, 80.00, 1, 1),

(1, 'dining-chair', 'Dining Chair',
 'A minimalist wooden dining chair with an ergonomic curved backrest.',
 249.00, 'RM', NULL, NULL, NULL, 45.00, 88.00, 52.00, 1, 2),

(2, 'three-seater-sofa', 'Three-Seater Sofa',
 'A spacious three-seater fabric sofa with deep cushions, ideal for a modern living room.',
 2399.00, 'RM', NULL, NULL, NULL, 210.00, 85.00, 90.00, 1, 3),

(3, 'coffee-table', 'Coffee Table',
 'A round coffee table with a tempered-glass top and a slim metal frame.',
 459.00, 'RM', NULL, NULL, NULL, 90.00, 45.00, 90.00, 1, 4),

(4, 'platform-bed', 'Platform Bed',
 'A low-profile queen platform bed frame with a padded headboard.',
 1799.00, 'RM', NULL, NULL, NULL, 160.00, 100.00, 210.00, 1, 5),

(6, 'floor-lamp', 'Floor Lamp',
 'A modern arc floor lamp with a fabric drum shade, ideal for ambient lighting.',
 329.00, 'RM', NULL, NULL, NULL, 40.00, 155.00, 40.00, 1, 6);
