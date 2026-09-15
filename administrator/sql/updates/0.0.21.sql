ALTER TABLE `#__fdshop_bundles`
  ADD COLUMN `image_path` VARCHAR(1024) NOT NULL DEFAULT '' AFTER `description`,
  ADD COLUMN `max_quantity_per_product` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `image_path`;

CREATE TABLE IF NOT EXISTS `#__fdshop_saved_bundles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `bundle_id` BIGINT UNSIGNED NOT NULL,
  `saved_name` VARCHAR(255) NOT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fdshop_saved_bundles_user_id` (`user_id`),
  KEY `idx_fdshop_saved_bundles_bundle_id` (`bundle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__fdshop_saved_bundle_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `saved_bundle_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_saved_bundle_items_product` (`saved_bundle_id`, `product_id`),
  KEY `idx_fdshop_saved_bundle_items_saved_bundle_id` (`saved_bundle_id`),
  KEY `idx_fdshop_saved_bundle_items_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__fdshop_cart_bundles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `session_id` VARCHAR(191) NOT NULL DEFAULT '',
  `bundle_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `bundle_number` VARCHAR(64) NOT NULL,
  `bundle_name` VARCHAR(255) NOT NULL,
  `image_path` VARCHAR(1024) NOT NULL DEFAULT '',
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',
  `distinct_product_count` INT UNSIGNED NOT NULL,
  `total_quantity` INT UNSIGNED NOT NULL,
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
  KEY `idx_fdshop_cart_bundles_user_id` (`user_id`),
  KEY `idx_fdshop_cart_bundles_session_id` (`session_id`),
  KEY `idx_fdshop_cart_bundles_bundle_id` (`bundle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__fdshop_cart_bundle_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cart_bundle_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `product_name` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(64) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `regular_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `effective_unit_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `bundle_discount_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `unit_price_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `unit_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `tax_rate` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
  `line_total_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `line_total_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_cart_bundle_items_product` (`cart_bundle_id`, `product_id`),
  KEY `idx_fdshop_cart_bundle_items_cart_bundle_id` (`cart_bundle_id`),
  KEY `idx_fdshop_cart_bundle_items_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
