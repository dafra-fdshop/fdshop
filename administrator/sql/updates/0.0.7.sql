-- FDShop update 0.0.7
-- Add config fields, shipments and payment tables

-- --------------------------------------------------------
-- #__fdshop_config erweitern
-- --------------------------------------------------------
ALTER TABLE `#__fdshop_config`
  ADD COLUMN `show_terms_checkbox` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN `require_terms_checkbox` TINYINT(1) NOT NULL DEFAULT 0;

-- --------------------------------------------------------
-- #__fdshop_shipments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_shipments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shipment_name` VARCHAR(255) NOT NULL,
  `shipment_description` TEXT NULL,
  `shipment_color` VARCHAR(50) NOT NULL DEFAULT '',
  `shipment_price` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_shipments_is_published` (`is_published`),
  KEY `idx_fdshop_shipments_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_payment_methods
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_payment_methods` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_name` VARCHAR(255) NOT NULL,
  `payment_description` TEXT NULL,
  `payment_fee` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `paypal_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_payment_methods_is_published` (`is_published`),
  KEY `idx_fdshop_payment_methods_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;