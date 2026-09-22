SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- #__fdshop_products
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `manufacturer_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `product_name` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(191) NOT NULL,
  `short_description` TEXT NULL,
  `description` MEDIUMTEXT NULL,
  `buyer_group_id` BIGINT UNSIGNED NOT NULL DEFAULT 1,

  `sale_price` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `discount_price` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `discount_active` TINYINT(1) NOT NULL DEFAULT 0,
  `currency` CHAR(3) NOT NULL,

  `min_order_qty` DECIMAL(12,3) NOT NULL,
  `max_order_qty` DECIMAL(12,3) NOT NULL,
  `step_order_qty` DECIMAL(12,3) NOT NULL,

  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `publish_up` DATETIME NULL DEFAULT NULL,
  `publish_down` DATETIME NULL DEFAULT NULL,

  `meta_title` VARCHAR(255) NOT NULL,
  `meta_keywords` TEXT NULL,
  `meta_description` TEXT NULL,

  `in_stock` VARCHAR(50) NOT NULL,
  `available_from` DATETIME NULL DEFAULT NULL,
  `unit_type` VARCHAR(100) NOT NULL DEFAULT 'Stück',

  `nem` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `shot_count` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `caliber` VARCHAR(100) NOT NULL DEFAULT '0.000',
  `burn_time` VARCHAR(100) NOT NULL DEFAULT '0.000',
  `rise_height` VARCHAR(100) NOT NULL DEFAULT '0.000',

  `ribbon_new` TINYINT(1) NOT NULL DEFAULT 0,
  `ribbon_hot` TINYINT(1) NOT NULL DEFAULT 0,
  `ribbon_bundle` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_fdshop_products_manufacturer_id` (`manufacturer_id`),
  KEY `idx_fdshop_products_buyer_group_id` (`buyer_group_id`),
  KEY `idx_fdshop_products_is_active` (`is_active`),
  KEY `idx_fdshop_products_is_deleted` (`is_deleted`),
  KEY `idx_fdshop_products_ribbon_bundle` (`ribbon_bundle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- #__fdshop_products_details
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_products_details` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `sku` VARCHAR(64) NOT NULL,
  `gtin` VARCHAR(32) NOT NULL,
  `bundle_eligible` TINYINT(1) NOT NULL DEFAULT 0,
  `stock_quantity` INT UNSIGNED NOT NULL,
  `low_stock` INT UNSIGNED NOT NULL,
  `reserved_quantity` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `sold_quantity` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `is_in_stock` TINYINT(1) NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NULL DEFAULT NULL,
  `weight` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `length` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `width` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `height` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `unit_quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `unit_discount_type` VARCHAR(20) NOT NULL DEFAULT 'none',
  `unit_discount_value` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  PRIMARY KEY (`id`),
  KEY `idx_fdshop_products_details_product_id` (`product_id`),
  KEY `idx_fdshop_products_details_bundle_eligible` (`bundle_eligible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- #__fdshop_product_prices
-- --------------------------------------------------------
CREATE TABLE `#__fdshop_product_prices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,

  `currency` CHAR(3) NOT NULL,
  `calc_price_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `calc_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `calc_stock_cost_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `calc_shipping_cost_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `calc_other_cost_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `calc_target_margin` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,

  `manu_price_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `manu_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `manu_stock_cost_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `manu_shipping_cost_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `manu_other_cost_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,

  `stock_rule_overridden` TINYINT(1) NOT NULL DEFAULT 0,
  `stock_rule_value` DECIMAL(12,4) NOT NULL,
  `shipping_rule_overridden` TINYINT(1) NOT NULL DEFAULT 0,
  `shipping_rule_value` DECIMAL(12,4) NOT NULL,
  `other_rule_overridden` TINYINT(1) NOT NULL DEFAULT 0,
  `other_rule_value` DECIMAL(12,4) NOT NULL,
  `tax_rate` DECIMAL(7,4) NOT NULL,
  `purchase_price_net` DECIMAL(12,4) NOT NULL,
  `margin_effective` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `created` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fdshop_product_prices_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_price_calc_rules
-- --------------------------------------------------------
CREATE TABLE `#__fdshop_price_calc_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stock_cost_pct` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `shipping_cost_pct` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `other_cost_pct` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `target_margin_pct` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_product_prices_research
-- --------------------------------------------------------
CREATE TABLE `#__fdshop_product_prices_research` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `research_data` JSON NOT NULL,
  `checked_at` DATETIME NULL DEFAULT NULL,
  `note` TEXT NULL,
  `created` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fdshop_product_prices_research_product_id` (`product_id`),
  KEY `idx_fdshop_product_prices_research_checked_at` (`checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `category_name` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(191) NOT NULL,
  `path` VARCHAR(512) NOT NULL DEFAULT '',
  `description` MEDIUMTEXT NULL,
  `level` INT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,

  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_categories_parent_id` (`parent_id`),
  KEY `idx_fdshop_categories_alias` (`alias`),
  KEY `idx_fdshop_categories_path` (`path`(191)),
  KEY `idx_fdshop_categories_is_active` (`is_active`),
  KEY `idx_fdshop_categories_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_product_category_map
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_product_category_map` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_product_category_map_product_category` (`product_id`, `category_id`),
  KEY `idx_fdshop_product_category_map_product_id` (`product_id`),
  KEY `idx_fdshop_product_category_map_category_id` (`category_id`),
  KEY `idx_fdshop_product_category_map_is_primary` (`is_primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_manufacturers
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_manufacturers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `manufacturer_name` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(191) NOT NULL,

  `description` TEXT NULL,
  `meta_title` VARCHAR(255) NOT NULL DEFAULT '',
  `meta_keywords` TEXT NULL,
  `meta_description` TEXT NULL,

  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,

  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_manufacturers_alias` (`alias`),
  KEY `idx_fdshop_manufacturers_is_active` (`is_active`),
  KEY `idx_fdshop_manufacturers_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_buyer_groups
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_buyer_groups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_name` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(191) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,

  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_buyer_groups_alias` (`alias`),
  KEY `idx_fdshop_buyer_groups_is_active` (`is_active`),
  KEY `idx_fdshop_buyer_groups_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_user_buyer_group_map
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_user_buyer_group_map` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `buyer_group_id` BIGINT UNSIGNED NOT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_user_buyer_group_map_user_group` (`user_id`, `buyer_group_id`),
  KEY `idx_fdshop_user_buyer_group_map_user_id` (`user_id`),
  KEY `idx_fdshop_user_buyer_group_map_buyer_group_id` (`buyer_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_product_buyer_group_map
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_product_buyer_group_map` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `buyer_group_id` BIGINT UNSIGNED NOT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_product_buyer_group_map_product_group` (`product_id`, `buyer_group_id`),
  KEY `idx_fdshop_product_buyer_group_map_product_id` (`product_id`),
  KEY `idx_fdshop_product_buyer_group_map_buyer_group_id` (`buyer_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- #__fdshop_bundles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_bundles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bundle_number` VARCHAR(64) NOT NULL,
  `bundle_name` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(191) NOT NULL,
  `description` MEDIUMTEXT NULL,
  `image_path` VARCHAR(1024) NOT NULL DEFAULT '',
  `max_quantity_per_product` INT UNSIGNED NOT NULL DEFAULT 1,
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
-- #__fdshop_saved_bundles / #__fdshop_saved_bundle_items
-- Personal editable compositions. Deliberately no price snapshots.
-- --------------------------------------------------------
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

-- --------------------------------------------------------
-- #__fdshop_cart_bundles / #__fdshop_cart_bundle_items
-- Validated purchase snapshots. They remain independent of saved bundles.
-- --------------------------------------------------------
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
-- #__fdshop_orders
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(64) NOT NULL,

  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `buyer_group_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `payment_method_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `shipment_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,

  `order_status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `order_status_id` INT UNSIGNED NOT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',

  `grand_total` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `has_bundle` TINYINT(1) NOT NULL DEFAULT 0,

  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` DATETIME NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_orders_order_number` (`order_number`),
  KEY `idx_fdshop_orders_user_id` (`user_id`),
  KEY `idx_fdshop_orders_buyer_group_id` (`buyer_group_id`),
  KEY `idx_fdshop_orders_payment_method_id` (`payment_method_id`),
  KEY `idx_fdshop_orders_shipment_id` (`shipment_id`),
  KEY `idx_fdshop_orders_order_status` (`order_status`),
  KEY `idx_fdshop_orders_order_status_id` (`order_status_id`),
  KEY `idx_fdshop_orders_state` (`state`),
  KEY `idx_fdshop_orders_has_bundle` (`has_bundle`),
  KEY `idx_fdshop_orders_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_order_items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,

  `product_name` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(64) NOT NULL,
  `gtin` VARCHAR(64) NOT NULL DEFAULT '',
  `manufacturer_name` VARCHAR(255) NOT NULL DEFAULT '',

  `quantity` DECIMAL(12,3) NOT NULL DEFAULT 1.000,
  `regular_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `discount_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `unit_price_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `unit_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `tax_rate` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
  `line_total_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `line_total_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',
  `is_removed` TINYINT(1) NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_order_items_order_id` (`order_id`),
  KEY `idx_fdshop_order_items_product_id` (`product_id`),
  KEY `idx_fdshop_order_items_sku` (`sku`),
  KEY `idx_fdshop_order_items_is_removed` (`is_removed`)
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
  `is_removed` TINYINT(1) NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` DATETIME NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_order_bundles_order_id` (`order_id`),
  KEY `idx_fdshop_order_bundles_bundle_id` (`bundle_id`),
  KEY `idx_fdshop_order_bundles_bundle_number` (`bundle_number`),
  KEY `idx_fdshop_order_bundles_is_removed` (`is_removed`),
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
  `gtin` VARCHAR(64) NOT NULL DEFAULT '',
  `manufacturer_name` VARCHAR(255) NOT NULL DEFAULT '',
  `quantity` DECIMAL(12,3) NOT NULL DEFAULT 1.000,
  `regular_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `discount_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `unit_price_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `unit_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `tax_rate` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
  `total_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `total_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',
  `is_removed` TINYINT(1) NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_order_bundle_items_order_bundle_id` (`order_bundle_id`),
  KEY `idx_fdshop_order_bundle_items_product_id` (`product_id`),
  KEY `idx_fdshop_order_bundle_items_sku` (`sku`),
  KEY `idx_fdshop_order_bundle_items_is_removed` (`is_removed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_order_statuses
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_order_statuses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `status_code` VARCHAR(32) NOT NULL,
  `status_name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,

  `notify_seller` TINYINT(1) NOT NULL DEFAULT 0,
  `notify_buyer` TINYINT(1) NOT NULL DEFAULT 0,
  `create_invoice` TINYINT(1) NOT NULL DEFAULT 0,

  `stock_action` VARCHAR(32) NOT NULL DEFAULT 'none',

  `seller_email_mode` VARCHAR(32) NOT NULL DEFAULT 'config',
  `seller_email_address` VARCHAR(255) NULL,

  `buyer_email_mode` VARCHAR(32) NOT NULL DEFAULT 'account',

  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,

  `created` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_order_statuses_status_code` (`status_code`),
  KEY `idx_fdshop_order_statuses_is_active` (`is_active`),
  KEY `idx_fdshop_order_statuses_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__fdshop_order_statuses` (
  `id`,
  `status_code`,
  `status_name`,
  `description`,
  `notify_seller`,
  `notify_buyer`,
  `create_invoice`,
  `stock_action`,
  `seller_email_mode`,
  `seller_email_address`,
  `buyer_email_mode`,
  `is_active`,
  `ordering`,
  `created`,
  `created_by`,
  `modified`,
  `modified_by`
)
SELECT
  1,
  'ordered',
  'Vom Kunden bestellt',
  NULL,
  1,
  1,
  0,
  'reserve',
  'config',
  NULL,
  'account',
  1,
  1,
  CURRENT_TIMESTAMP,
  0,
  NULL,
  0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `#__fdshop_order_statuses` WHERE `status_code` = 'ordered'
);

INSERT INTO `#__fdshop_order_statuses` (
  `id`,
  `status_code`,
  `status_name`,
  `description`,
  `notify_seller`,
  `notify_buyer`,
  `create_invoice`,
  `stock_action`,
  `seller_email_mode`,
  `seller_email_address`,
  `buyer_email_mode`,
  `is_active`,
  `ordering`,
  `created`,
  `created_by`,
  `modified`,
  `modified_by`
)
SELECT
  2,
  'paid',
  'Zahlung erhalten',
  NULL,
  1,
  1,
  1,
  'reserve',
  'config',
  NULL,
  'account',
  1,
  2,
  CURRENT_TIMESTAMP,
  0,
  NULL,
  0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `#__fdshop_order_statuses` WHERE `status_code` = 'paid'
);

INSERT INTO `#__fdshop_order_statuses` (
  `id`,
  `status_code`,
  `status_name`,
  `description`,
  `notify_seller`,
  `notify_buyer`,
  `create_invoice`,
  `stock_action`,
  `seller_email_mode`,
  `seller_email_address`,
  `buyer_email_mode`,
  `is_active`,
  `ordering`,
  `created`,
  `created_by`,
  `modified`,
  `modified_by`
)
SELECT
  3,
  'packed',
  'Bestellung gepackt',
  NULL,
  0,
  1,
  0,
  'none',
  'config',
  NULL,
  'account',
  1,
  3,
  CURRENT_TIMESTAMP,
  0,
  NULL,
  0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `#__fdshop_order_statuses` WHERE `status_code` = 'packed'
);

INSERT INTO `#__fdshop_order_statuses` (
  `id`,
  `status_code`,
  `status_name`,
  `description`,
  `notify_seller`,
  `notify_buyer`,
  `create_invoice`,
  `stock_action`,
  `seller_email_mode`,
  `seller_email_address`,
  `buyer_email_mode`,
  `is_active`,
  `ordering`,
  `created`,
  `created_by`,
  `modified`,
  `modified_by`
)
SELECT
  4,
  'shipped',
  'Versendet',
  NULL,
  0,
  1,
  0,
  'deduct',
  'config',
  NULL,
  'account',
  1,
  4,
  CURRENT_TIMESTAMP,
  0,
  NULL,
  0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `#__fdshop_order_statuses` WHERE `status_code` = 'shipped'
);

INSERT INTO `#__fdshop_order_statuses` (
  `id`,
  `status_code`,
  `status_name`,
  `description`,
  `notify_seller`,
  `notify_buyer`,
  `create_invoice`,
  `stock_action`,
  `seller_email_mode`,
  `seller_email_address`,
  `buyer_email_mode`,
  `is_active`,
  `ordering`,
  `created`,
  `created_by`,
  `modified`,
  `modified_by`
)
SELECT
  5,
  'cancelled',
  'Storniert',
  NULL,
  1,
  1,
  0,
  'available',
  'config',
  NULL,
  'account',
  1,
  5,
  CURRENT_TIMESTAMP,
  0,
  NULL,
  0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `#__fdshop_order_statuses` WHERE `status_code` = 'cancelled'
);

INSERT INTO `#__fdshop_order_statuses` (
  `id`,
  `status_code`,
  `status_name`,
  `description`,
  `notify_seller`,
  `notify_buyer`,
  `create_invoice`,
  `stock_action`,
  `seller_email_mode`,
  `seller_email_address`,
  `buyer_email_mode`,
  `is_active`,
  `ordering`,
  `created`,
  `created_by`,
  `modified`,
  `modified_by`
)
SELECT
  6,
  'completed',
  'Abgeschlossen',
  NULL,
  0,
  1,
  0,
  'deduct',
  'config',
  NULL,
  'account',
  1,
  6,
  CURRENT_TIMESTAMP,
  0,
  NULL,
  0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `#__fdshop_order_statuses` WHERE `status_code` = 'completed'
);

-- --------------------------------------------------------
-- #__fdshop_order_history
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_order_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,

  `event_type` VARCHAR(50) NOT NULL,
  `event_title` VARCHAR(255) NOT NULL,
  `event_text` TEXT NULL,

  `reference_type` VARCHAR(50) NULL,
  `reference_id` BIGINT UNSIGNED NULL,

  `is_system_event` TINYINT(1) NOT NULL DEFAULT 0,

  `created` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_order_history_order_id` (`order_id`),
  KEY `idx_fdshop_order_history_event_type` (`event_type`),
  KEY `idx_fdshop_order_history_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_order_status_history
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_order_status_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `old_status_id` INT UNSIGNED NULL,
  `new_status_id` INT UNSIGNED NOT NULL,

  `comment` TEXT NULL,
  `is_system_change` TINYINT(1) NOT NULL DEFAULT 0,

  `changed_at` DATETIME NOT NULL,
  `changed_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_order_status_history_order_id` (`order_id`),
  KEY `idx_fdshop_order_status_history_old_status_id` (`old_status_id`),
  KEY `idx_fdshop_order_status_history_new_status_id` (`new_status_id`),
  KEY `idx_fdshop_order_status_history_changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_config
-- globale Einzel-Datensatz-Konfiguration
-- --------------------------------------------------------
CREATE TABLE `#__fdshop_config` (
  `id` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `general_vat_rate` DECIMAL(7,4) NOT NULL DEFAULT 19.0000,
  `general_currency` CHAR(3) NOT NULL DEFAULT 'EUR',
  `image_size_default` INT UNSIGNED NOT NULL DEFAULT 550,
  `image_quality_default` TINYINT UNSIGNED NOT NULL DEFAULT 80,
  `image_size_small` INT UNSIGNED NOT NULL DEFAULT 200,
  `image_quality_small` TINYINT UNSIGNED NOT NULL DEFAULT 60,
  `image_size_mobile` INT UNSIGNED NOT NULL DEFAULT 250,
  `image_quality_mobile` TINYINT UNSIGNED NOT NULL DEFAULT 70,
  `image_size_manufacturer` INT UNSIGNED NOT NULL DEFAULT 400,
  `show_terms_checkbox` TINYINT(1) NOT NULL DEFAULT 0,
  `require_terms_checkbox` TINYINT(1) NOT NULL DEFAULT 0,
  `katalog_active` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__fdshop_config` (
  `id`,
  `general_vat_rate`,
  `general_currency`,
  `image_size_default`,
  `image_quality_default`,
  `image_size_small`,
  `image_quality_small`,
  `image_size_mobile`,
  `image_quality_mobile`,
  `image_size_manufacturer`,
  `show_terms_checkbox`,
  `require_terms_checkbox`,
  `katalog_active`
)
SELECT
  1,
  19.0000,
  'EUR',
  550,
  80,
  200,
  60,
  250,
  70,
  400,
  0,
  0,
  0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1
  FROM `#__fdshop_config`
  WHERE `id` = 1
);

-- --------------------------------------------------------
-- #__fdshop_media
-- Produktbilder / Medienverwaltun
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_media` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,

  `media_type` VARCHAR(20) NOT NULL DEFAULT 'image',
  `external_url` VARCHAR(500) NULL DEFAULT NULL,

  `file_name` VARCHAR(255) NULL DEFAULT NULL,
  `file_type` VARCHAR(50) NULL DEFAULT NULL,

  `path_standard` VARCHAR(500) NULL DEFAULT NULL,
  `path_small` VARCHAR(500) NULL DEFAULT NULL,
  `path_mobile` VARCHAR(500) NULL DEFAULT NULL,
  `path_invoice` VARCHAR(500) NULL DEFAULT NULL,

  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `ordering` INT NOT NULL DEFAULT 0,

  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_media_product_id` (`product_id`),
  KEY `idx_fdshop_media_is_primary` (`is_primary`),
  KEY `idx_fdshop_media_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- --------------------------------------------------------
-- #__fdshop_cart
-- --------------------------------------------------------
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
  `unit_variant` VARCHAR(20) NOT NULL DEFAULT 'piece',
  `unit_type_snapshot` VARCHAR(100) NOT NULL DEFAULT 'Stück',
  `unit_quantity_snapshot` INT UNSIGNED NOT NULL DEFAULT 1,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` DATETIME NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_cart_user_id` (`user_id`),
  KEY `idx_fdshop_cart_session_id` (`session_id`),
  KEY `idx_fdshop_cart_product_id` (`product_id`),
  KEY `idx_fdshop_cart_variant` (`product_id`, `unit_variant`, `unit_type_snapshot`, `unit_quantity_snapshot`),
  KEY `idx_fdshop_cart_buyer_group_id` (`buyer_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- #__fdshop_shipments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_shipments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shipment_name` VARCHAR(255) NOT NULL,
  `shipment_description` TEXT NULL,
  `shipment_color` VARCHAR(50) NOT NULL DEFAULT '',
  `shipment_price` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `published` TINYINT(1) NOT NULL DEFAULT 1,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_shipments_published` (`published`),
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
  `published` TINYINT(1) NOT NULL DEFAULT 1,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_payment_methods_published` (`published`),
  KEY `idx_fdshop_payment_methods_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_filters / #__fdshop_filter_ranges
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_filters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filter_key` VARCHAR(32) NOT NULL,
  `label` VARCHAR(100) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_filters_key` (`filter_key`),
  KEY `idx_fdshop_filters_active_ordering` (`is_active`, `ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__fdshop_filter_ranges` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filter_id` BIGINT UNSIGNED NOT NULL,
  `label` VARCHAR(100) NOT NULL,
  `value_from` DECIMAL(12,3) NULL DEFAULT NULL,
  `value_to` DECIMAL(12,3) NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_fdshop_filter_ranges_filter_active_ordering` (`filter_id`, `is_active`, `ordering`),
  CONSTRAINT `fk_fdshop_filter_ranges_filter` FOREIGN KEY (`filter_id`) REFERENCES `#__fdshop_filters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__fdshop_filters` (`filter_key`, `label`, `is_active`, `ordering`) VALUES
('manufacturer', 'Hersteller', 1, 10),
('availability', 'Verfügbarkeit', 1, 20),
('duration', 'Brenndauer', 1, 30),
('caliber', 'Kaliber', 1, 40),
('nem', 'NEM', 1, 50)
ON DUPLICATE KEY UPDATE `filter_key` = VALUES(`filter_key`);

INSERT INTO `#__fdshop_filter_ranges` (`filter_id`, `label`, `value_from`, `value_to`, `is_active`, `ordering`)
SELECT f.id, seed.label, seed.value_from, seed.value_to, 1, seed.ordering
FROM `#__fdshop_filters` f
JOIN (
  SELECT 'duration' filter_key, '0 bis 20 s' label, 0.000 value_from, 20.000 value_to, 10 ordering
  UNION ALL SELECT 'duration', '20 bis 40 s', 20.000, 40.000, 20
  UNION ALL SELECT 'duration', 'mehr als 40 s', 40.000, NULL, 30
  UNION ALL SELECT 'caliber', '0 bis 20 mm', 0.000, 20.000, 10
  UNION ALL SELECT 'caliber', '20 bis 25 mm', 20.000, 25.000, 20
  UNION ALL SELECT 'caliber', '25 bis 30 mm', 25.000, 30.000, 30
  UNION ALL SELECT 'nem', '0 bis 200 g', 0.000, 200.000, 10
  UNION ALL SELECT 'nem', '200 bis 400 g', 200.000, 400.000, 20
  UNION ALL SELECT 'nem', '400 bis 500 g', 400.000, 500.000, 30
) seed ON seed.filter_key = f.filter_key
WHERE NOT EXISTS (SELECT 1 FROM `#__fdshop_filter_ranges` r WHERE r.filter_id = f.id);

CREATE TABLE IF NOT EXISTS `#__fdshop_filter_category_map` (
  `filter_id` BIGINT UNSIGNED NOT NULL, `category_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`filter_id`,`category_id`), KEY `idx_fdshop_filter_category_category` (`category_id`),
  CONSTRAINT `fk_fdshop_filter_category_filter` FOREIGN KEY (`filter_id`) REFERENCES `#__fdshop_filters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fdshop_filter_category_category` FOREIGN KEY (`category_id`) REFERENCES `#__fdshop_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `#__fdshop_filter_range_category_map` (
  `range_id` BIGINT UNSIGNED NOT NULL, `category_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`range_id`,`category_id`), KEY `idx_fdshop_filter_range_category_category` (`category_id`),
  CONSTRAINT `fk_fdshop_filter_range_category_range` FOREIGN KEY (`range_id`) REFERENCES `#__fdshop_filter_ranges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fdshop_filter_range_category_category` FOREIGN KEY (`category_id`) REFERENCES `#__fdshop_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `#__fdshop_filter_options` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `filter_id` BIGINT UNSIGNED NOT NULL, `option_key` VARCHAR(64) NOT NULL,
  `label` VARCHAR(100) NOT NULL, `is_active` TINYINT(1) NOT NULL DEFAULT 1, `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_fdshop_filter_options_key` (`filter_id`,`option_key`), KEY `idx_fdshop_filter_options_active_ordering` (`filter_id`,`is_active`,`ordering`),
  CONSTRAINT `fk_fdshop_filter_options_filter` FOREIGN KEY (`filter_id`) REFERENCES `#__fdshop_filters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `#__fdshop_filter_option_category_map` (
  `option_id` BIGINT UNSIGNED NOT NULL, `category_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`option_id`,`category_id`), KEY `idx_fdshop_filter_option_category_category` (`category_id`),
  CONSTRAINT `fk_fdshop_filter_option_category_option` FOREIGN KEY (`option_id`) REFERENCES `#__fdshop_filter_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fdshop_filter_option_category_category` FOREIGN KEY (`category_id`) REFERENCES `#__fdshop_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `#__fdshop_product_filter_option_map` (
  `product_id` BIGINT UNSIGNED NOT NULL, `option_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`product_id`,`option_id`), KEY `idx_fdshop_product_filter_option_option` (`option_id`,`product_id`),
  CONSTRAINT `fk_fdshop_product_filter_option_product` FOREIGN KEY (`product_id`) REFERENCES `#__fdshop_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fdshop_product_filter_option_option` FOREIGN KEY (`option_id`) REFERENCES `#__fdshop_filter_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `#__fdshop_filters` (`filter_key`,`label`,`is_active`,`ordering`) VALUES ('firing_type','Abschuss',1,60),('product_type','Art',1,70) ON DUPLICATE KEY UPDATE `filter_key`=VALUES(`filter_key`);
INSERT INTO `#__fdshop_filter_options` (`filter_id`,`option_key`,`label`,`is_active`,`ordering`)
SELECT f.id,s.option_key,s.label,1,s.ordering FROM `#__fdshop_filters` f JOIN (
 SELECT 'firing_type' filter_key,'straight' option_key,'Gerade' label,10 ordering UNION ALL SELECT 'firing_type','fanned','Gefächert',20
 UNION ALL SELECT 'product_type','ground_firework','Bodenfeuerwerk',10 UNION ALL SELECT 'product_type','firebird','Feuervögel',20
 UNION ALL SELECT 'product_type','roman_candle','Römische Lichter',30 UNION ALL SELECT 'product_type','fountain_volcano','Vulkane & Fontänen',40
) s ON s.filter_key=f.filter_key LEFT JOIN `#__fdshop_filter_options` o ON o.filter_id=f.id AND o.option_key=s.option_key WHERE o.id IS NULL;

SET FOREIGN_KEY_CHECKS = 1;
