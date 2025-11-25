-- schema.sql – Estructura base para WinePick (sin datos)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `winepick_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `winepick_db`;

-- --------------------------------------------------------
-- Tabla: combo_promotions
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `combo_promotions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `product1_id` bigint(20) UNSIGNED NOT NULL,
  `product2_id` bigint(20) UNSIGNED NOT NULL,
  `combo_price` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `note` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_cp_products` (`product1_id`,`product2_id`),
  KEY `idx_cp_dates` (`start_date`,`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: products
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `products` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `producer` varchar(255) DEFAULT NULL,
  `varietal` varchar(255) DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `year` smallint(6) DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `list_price` decimal(10,2) NOT NULL,
  `stock_status` enum('AVAILABLE','LOW','OUT') NOT NULL DEFAULT 'AVAILABLE',
  `main_image` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `qr_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: product_images
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `product_images` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `path` varchar(255) NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: product_promotions
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `product_promotions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `percent` decimal(5,2) DEFAULT NULL,
  `pack_size` int(11) DEFAULT NULL,
  `pack_price` decimal(10,2) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `note` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_pp_product` (`product_id`),
  KEY `idx_pp_product_dates` (`product_id`,`start_date`,`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: users
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: view_events
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `view_events` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `channel` enum('QR','SEARCH') NOT NULL,
  `viewed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `qr_code` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ve_product_date` (`product_id`,`viewed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Foreign keys
-- --------------------------------------------------------

ALTER TABLE `combo_promotions`
  ADD CONSTRAINT `fk_cp_product1`
    FOREIGN KEY (`product1_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cp_product2`
    FOREIGN KEY (`product2_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_product_images_products`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `product_promotions`
  ADD CONSTRAINT `fk_pp_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `view_events`
  ADD CONSTRAINT `fk_ve_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;
