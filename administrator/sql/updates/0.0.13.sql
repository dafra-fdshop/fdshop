-- FDShop update 0.0.13
-- Add database schema for bundle module

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `#__fdshop_products`
  ADD COLUMN `ribbon_bundle` TINYINT(1) NOT NULL DEFAULT 0 AFTER `ribbon_hot`,
  ADD KEY `idx_fdshop_products_ribbon_bundle` (`ribbon_bundle`);

ALTER TABLE `#__fdshop_orders`
  ADD COLUMN `has_bundle` TINYINT(1) NOT NULL DEFAULT 0 AFTER `grand_total`,
  ADD KEY `idx_fdshop_orders_has_bundle` (`has_bundle`);

-- --------------------------------------------------------
-- #__fdshop_bundles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_bundles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bundle_number` VARCHAR(64) NOT NULL,
  `bundle_name` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(191) NOT NULL,
  `description` MEDIUMTEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_bundles_bundle_number` (`bundle_number`),
  KEY `idx_fdshop_bundles_alias` (`alias`),
  KEY `idx_fdshop_bundles_is_active` (`is_active`),
  KEY `idx_fdshop_bundles_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_bundle_items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_bundle_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bundle_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_bundle_items_bundle_product` (`bundle_id`, `product_id`),
  KEY `idx_fdshop_bundle_items_bundle_id` (`bundle_id`),
  KEY `idx_fdshop_bundle_items_product_id` (`product_id`),
  KEY `idx_fdshop_bundle_items_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_bundle_discount_rules
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_bundle_discount_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bundle_id` BIGINT UNSIGNED NOT NULL,
  `min_quantity` DECIMAL(12,3) NOT NULL DEFAULT 1.000,
  `discount_percent` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_bundle_discount_rules_bundle_id` (`bundle_id`),
  KEY `idx_fdshop_bundle_discount_rules_min_quantity` (`min_quantity`),
  KEY `idx_fdshop_bundle_discount_rules_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_order_bundles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_order_bundles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `bundle_id` BIGINT UNSIGNED NOT NULL,
  `bundle_number` VARCHAR(64) NOT NULL,
  `bundle_name` VARCHAR(255) NOT NULL,
  `quantity_items` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `subtotal_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `subtotal_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `discount_percent` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
  `discount_amount_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `discount_amount_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `total_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `total_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` DATETIME NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_order_bundles_order_id` (`order_id`),
  KEY `idx_fdshop_order_bundles_bundle_id` (`bundle_id`),
  KEY `idx_fdshop_order_bundles_bundle_number` (`bundle_number`),
  KEY `idx_fdshop_order_bundles_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_order_bundle_items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_order_bundle_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_bundle_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(64) NOT NULL,
  `quantity` DECIMAL(12,3) NOT NULL DEFAULT 1.000,
  `unit_price_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `unit_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `total_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `total_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_order_bundle_items_order_bundle_id` (`order_bundle_id`),
  KEY `idx_fdshop_order_bundle_items_product_id` (`product_id`),
  KEY `idx_fdshop_order_bundle_items_sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
