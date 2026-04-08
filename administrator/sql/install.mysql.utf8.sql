SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- #__fdshop_products
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sku` VARCHAR(64) NOT NULL,
  `gtin` VARCHAR(32) NOT NULL DEFAULT '',
  `manufacturer_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `product_name` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(191) NOT NULL,
  `short_description` TEXT NULL,
  `description` MEDIUMTEXT NULL,

  `active_price_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `active_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `active_tax_rate` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',

  `stock_quantity` INT NOT NULL DEFAULT 0,
  `reserved_quantity` INT NOT NULL DEFAULT 0,

  `min_order_qty` DECIMAL(12,3) NOT NULL DEFAULT 1.000,
  `max_order_qty` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `step_order_qty` DECIMAL(12,3) NOT NULL DEFAULT 1.000,

  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `state` TINYINT NOT NULL DEFAULT 1,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `access` INT UNSIGNED NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,

  `publish_up` DATETIME NULL DEFAULT NULL,
  `publish_down` DATETIME NULL DEFAULT NULL,

  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,

  `meta_title` VARCHAR(255) NOT NULL DEFAULT '',
  `meta_keywords` TEXT NULL,
  `meta_description` TEXT NULL,

  `discount_price` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `discount_active` TINYINT(1) NOT NULL DEFAULT 0,

  `is_in_stock` TINYINT(1) NOT NULL DEFAULT 1,
  `available_from` DATETIME NULL DEFAULT NULL,
  `sold_quantity` INT NOT NULL DEFAULT 0,

  `unit_type` VARCHAR(255) NOT NULL DEFAULT '',
  `unit_quantity` INT NOT NULL DEFAULT 0,

  `nem` FLOAT NOT NULL DEFAULT 0,
  `shot_count` INT NOT NULL DEFAULT 0,
  `caliber` VARCHAR(255) NOT NULL DEFAULT '',
  `burn_time` VARCHAR(255) NOT NULL DEFAULT '',
  `rise_height` VARCHAR(255) NOT NULL DEFAULT '',

  `weight` FLOAT NOT NULL DEFAULT 0,
  `length` FLOAT NOT NULL DEFAULT 0,
  `width` FLOAT NOT NULL DEFAULT 0,
  `height` FLOAT NOT NULL DEFAULT 0,

  `purchase_price` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `sale_price` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_products_sku` (`sku`),
  KEY `idx_fdshop_products_manufacturer_id` (`manufacturer_id`),
  KEY `idx_fdshop_products_alias` (`alias`),
  KEY `idx_fdshop_products_is_active` (`is_active`),
  KEY `idx_fdshop_products_state` (`state`),
  KEY `idx_fdshop_products_is_featured` (`is_featured`),
  KEY `idx_fdshop_products_ordering` (`ordering`)
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
-- #__fdshop_product_prices
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_product_prices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `buyer_group_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `price_type` VARCHAR(32) NOT NULL DEFAULT 'base',
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',

  `price_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `tax_rate` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,

  `min_qty` DECIMAL(12,3) NOT NULL DEFAULT 1.000,
  `max_qty` DECIMAL(12,3) NOT NULL DEFAULT 0.000,

  `valid_from` DATETIME NULL DEFAULT NULL,
  `valid_to` DATETIME NULL DEFAULT NULL,

  `priority` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_product_prices_product_id` (`product_id`),
  KEY `idx_fdshop_product_prices_buyer_group_id` (`buyer_group_id`),
  KEY `idx_fdshop_product_prices_price_type` (`price_type`),
  KEY `idx_fdshop_product_prices_valid_from` (`valid_from`),
  KEY `idx_fdshop_product_prices_valid_to` (`valid_to`),
  KEY `idx_fdshop_product_prices_is_active` (`is_active`),
  KEY `idx_fdshop_product_prices_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- #__fdshop_product_price_calc
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_product_price_calc` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,

  `price_type` VARCHAR(32) NOT NULL DEFAULT '',
  `source` VARCHAR(64) NOT NULL DEFAULT '',
  `label` VARCHAR(255) NOT NULL DEFAULT '',
  `competitor_name` VARCHAR(255) NOT NULL DEFAULT '',
  `source_url` VARCHAR(1024) NOT NULL DEFAULT '',

  `price_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',
  `checked_at` DATETIME NULL DEFAULT NULL,
  `note` TEXT NULL,

  `supplier_cost_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `shipping_cost_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `other_cost_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `target_margin_percent` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,

  `calc_price_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `calc_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `tax_rate` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,

  `is_current` TINYINT(1) NOT NULL DEFAULT 1,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_product_price_calc_product_id` (`product_id`),
  KEY `idx_fdshop_product_price_calc_price_type` (`price_type`),
  KEY `idx_fdshop_product_price_calc_source` (`source`),
  KEY `idx_fdshop_product_price_calc_checked_at` (`checked_at`),
  KEY `idx_fdshop_product_price_calc_is_current` (`is_current`)
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

  `quantity` DECIMAL(12,3) NOT NULL DEFAULT 1.000,
  `unit_price_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `unit_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_order_items_order_id` (`order_id`),
  KEY `idx_fdshop_order_items_product_id` (`product_id`),
  KEY `idx_fdshop_order_items_sku` (`sku`)
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
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,

  `event_type` VARCHAR(50) NOT NULL,
  `event_title` VARCHAR(255) NOT NULL,
  `event_text` TEXT NULL,

  `reference_type` VARCHAR(50) NULL,
  `reference_id` INT UNSIGNED NULL,

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
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
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
CREATE TABLE IF NOT EXISTS `#__fdshop_config` (
  `id` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `general_vat_rate` DECIMAL(7,4) NOT NULL DEFAULT 19.0000,
  `image_size_default` INT UNSIGNED NOT NULL DEFAULT 400,
  `image_size_small` INT UNSIGNED NOT NULL DEFAULT 250,
  `image_size_mobile` INT UNSIGNED NOT NULL DEFAULT 100,
  `image_size_manufacturer` INT UNSIGNED NOT NULL DEFAULT 400,
  `show_terms_checkbox` TINYINT(1) NOT NULL DEFAULT 0,
  `require_terms_checkbox` TINYINT(1) NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__fdshop_config` (
  `id`,
  `general_vat_rate`,
  `image_size_default`,
  `image_size_small`,
  `image_size_mobile`,
  `image_size_manufacturer`,
  `show_terms_checkbox`,
  `require_terms_checkbox`
)
SELECT
  1,
  19.0000,
  400,
  250,
  100,
  400,
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
-- Produktbilder / Medienverwaltung
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__fdshop_media` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,

  `file_name` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(50) NOT NULL,

  `path_standard` VARCHAR(500) NOT NULL,
  `path_small` VARCHAR(500) NOT NULL,
  `path_mobile` VARCHAR(500) NOT NULL,
  `path_invoice` VARCHAR(500) NOT NULL,

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
-- #__fdshop_orders_history
-- --------------------------------------------------------
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
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` DATETIME NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_cart_user_id` (`user_id`),
  KEY `idx_fdshop_cart_session_id` (`session_id`),
  KEY `idx_fdshop_cart_product_id` (`product_id`),
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

SET FOREIGN_KEY_CHECKS = 1;