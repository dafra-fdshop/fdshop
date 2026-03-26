-- FDShop update 0.0.6
-- Add orders history and cart tables and rename product dimension fields

ALTER TABLE `#__fdshop_products`
  CHANGE `weight_kg` `weight` FLOAT NOT NULL DEFAULT 0,
  CHANGE `length_cm` `length` FLOAT NOT NULL DEFAULT 0,
  CHANGE `width_cm` `width` FLOAT NOT NULL DEFAULT 0,
  CHANGE `height_cm` `height` FLOAT NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `#__fdshop_orders_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `history_type` VARCHAR(255) NOT NULL,
  `old_value` TEXT NULL,
  `new_value` TEXT NULL,
  `note` TEXT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_orders_history_order_id` (`order_id`),
  KEY `idx_fdshop_orders_history_history_type` (`history_type`),
  KEY `idx_fdshop_orders_history_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__fdshop_cart` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `session_id` VARCHAR(255) NOT NULL DEFAULT '',
  `product_id` BIGINT UNSIGNED NOT NULL,
  `buyer_group_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `quantity` DECIMAL(12,3) NOT NULL DEFAULT 1.000,
  `unit_price_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `unit_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` DATETIME NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_cart_user_id` (`user_id`),
  KEY `idx_fdshop_cart_session_id` (`session_id`),
  KEY `idx_fdshop_cart_product_id` (`product_id`),
  KEY `idx_fdshop_cart_buyer_group_id` (`buyer_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;