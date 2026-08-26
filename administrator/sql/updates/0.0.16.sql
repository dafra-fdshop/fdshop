-- FDShop update 0.0.16
-- Add coupon database structure and mapping tables

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- #__fdshop_coupons
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_coupons` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_code` VARCHAR(64) NOT NULL,
  `coupon_name` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(191) NOT NULL,
  `description` MEDIUMTEXT NULL,
  `discount_type` VARCHAR(32) NOT NULL DEFAULT 'percent',
  `discount_value` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `minimum_order_total` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `usage_limit_total` INT UNSIGNED NOT NULL DEFAULT 0,
  `usage_limit_per_user` INT UNSIGNED NOT NULL DEFAULT 0,
  `valid_from` DATETIME NULL DEFAULT NULL,
  `valid_to` DATETIME NULL DEFAULT NULL,
  `published` TINYINT(1) NOT NULL DEFAULT 0,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_coupons_coupon_code` (`coupon_code`),
  KEY `idx_fdshop_coupons_alias` (`alias`),
  KEY `idx_fdshop_coupons_published` (`published`),
  KEY `idx_fdshop_coupons_valid_from` (`valid_from`),
  KEY `idx_fdshop_coupons_valid_to` (`valid_to`),
  KEY `idx_fdshop_coupons_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_coupon_user_map
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_coupon_user_map` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_coupon_user_map_coupon_user` (`coupon_id`, `user_id`),
  KEY `idx_fdshop_coupon_user_map_coupon_id` (`coupon_id`),
  KEY `idx_fdshop_coupon_user_map_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_coupon_buyer_group_map
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_coupon_buyer_group_map` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_id` BIGINT UNSIGNED NOT NULL,
  `buyer_group_id` BIGINT UNSIGNED NOT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_coupon_buyer_group_map_coupon_group` (`coupon_id`, `buyer_group_id`),
  KEY `idx_fdshop_coupon_buyer_group_map_coupon_id` (`coupon_id`),
  KEY `idx_fdshop_coupon_buyer_group_map_buyer_group_id` (`buyer_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_coupon_product_map
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_coupon_product_map` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_coupon_product_map_coupon_product` (`coupon_id`, `product_id`),
  KEY `idx_fdshop_coupon_product_map_coupon_id` (`coupon_id`),
  KEY `idx_fdshop_coupon_product_map_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_coupon_category_map
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_coupon_category_map` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_id` BIGINT UNSIGNED NOT NULL,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_coupon_category_map_coupon_category` (`coupon_id`, `category_id`),
  KEY `idx_fdshop_coupon_category_map_coupon_id` (`coupon_id`),
  KEY `idx_fdshop_coupon_category_map_category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_coupon_usage
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_coupon_usage` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `coupon_code` VARCHAR(64) NOT NULL,
  `discount_amount_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `discount_amount_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `used_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_coupon_usage_coupon_id` (`coupon_id`),
  KEY `idx_fdshop_coupon_usage_order_id` (`order_id`),
  KEY `idx_fdshop_coupon_usage_user_id` (`user_id`),
  KEY `idx_fdshop_coupon_usage_coupon_code` (`coupon_code`),
  KEY `idx_fdshop_coupon_usage_used_at` (`used_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
