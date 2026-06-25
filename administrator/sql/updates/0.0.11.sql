-- FDShop update 0.0.11
-- Reorganize documented product-related tables and add general_currency to config

SET FOREIGN_KEY_CHECKS = 0;

-- ========================================================
-- 1) CONFIG erweitern
-- ========================================================

ALTER TABLE `#__fdshop_config`
  ADD COLUMN `general_currency` CHAR(3) NOT NULL DEFAULT 'EUR' AFTER `general_vat_rate`;

UPDATE `#__fdshop_config`
SET `general_currency` = 'EUR'
WHERE `general_currency` = '' OR `general_currency` IS NULL;


-- ========================================================
-- 2) #__fdshop_products -> neuer Sollstand
--    Alte Tabelle sichern, neue Tabelle anlegen, Daten übernehmen
-- ========================================================

RENAME TABLE `#__fdshop_products` TO `#__fdshop_products_old`;

CREATE TABLE `#__fdshop_products` (
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
  `katalog_active` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `publish_up` DATETIME NULL DEFAULT NULL,
  `publish_down` DATETIME NULL DEFAULT NULL,
  `meta_title` VARCHAR(255) NOT NULL,
  `meta_keywords` TEXT NULL,
  `meta_description` TEXT NULL,
  `in_stock` VARCHAR(50) NOT NULL,
  `available_from` DATETIME NULL DEFAULT NULL,
  `unit_type` VARCHAR(100) NOT NULL DEFAULT 'Stück',
  `unit_quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `nem` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `shot_count` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `caliber` VARCHAR(100) NOT NULL DEFAULT '0.000',
  `burn_time` VARCHAR(100) NOT NULL DEFAULT '0.000',
  `rise_height` VARCHAR(100) NOT NULL DEFAULT '0.000',
  `ribbon_new` TINYINT(1) NOT NULL DEFAULT 0,
  `ribbon_hot` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_fdshop_products_manufacturer_id` (`manufacturer_id`),
  KEY `idx_fdshop_products_buyer_group_id` (`buyer_group_id`),
  KEY `idx_fdshop_products_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__fdshop_products` (
  `id`,
  `manufacturer_id`,
  `product_name`,
  `alias`,
  `short_description`,
  `description`,
  `buyer_group_id`,
  `sale_price`,
  `discount_price`,
  `discount_active`,
  `currency`,
  `min_order_qty`,
  `max_order_qty`,
  `step_order_qty`,
  `katalog_active`,
  `is_active`,
  `publish_up`,
  `publish_down`,
  `meta_title`,
  `meta_keywords`,
  `meta_description`,
  `in_stock`,
  `available_from`,
  `unit_type`,
  `unit_quantity`,
  `nem`,
  `shot_count`,
  `caliber`,
  `burn_time`,
  `rise_height`,
  `ribbon_new`,
  `ribbon_hot`
)
SELECT
  `id`,
  `manufacturer_id`,
  `product_name`,
  `alias`,
  `short_description`,
  `description`,
  1,
  `sale_price`,
  `discount_price`,
  `discount_active`,
  `currency`,
  `min_order_qty`,
  `max_order_qty`,
  `step_order_qty`,
  0,
  `is_active`,
  `publish_up`,
  `publish_down`,
  `meta_title`,
  `meta_keywords`,
  `meta_description`,
  CASE
    WHEN `is_in_stock` = 1 THEN 'in_stock'
    ELSE 'out_of_stock'
  END,
  `available_from`,
  `unit_type`,
  `unit_quantity`,
  `nem`,
  `shot_count`,
  `caliber`,
  `burn_time`,
  `rise_height`,
  0,
  0
FROM `#__fdshop_products_old`;


-- ========================================================
-- 3) #__fdshop_products_details neu anlegen + Daten auslagern
-- ========================================================

CREATE TABLE `#__fdshop_products_details` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `sku` VARCHAR(64) NOT NULL,
  `gtin` VARCHAR(32) NOT NULL,
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
  PRIMARY KEY (`id`),
  KEY `idx_fdshop_product_details_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__fdshop_products_details` (
  `id`,
  `product_id`,
  `sku`,
  `gtin`,
  `stock_quantity`,
  `low_stock`,
  `reserved_quantity`,
  `sold_quantity`,
  `is_in_stock`,
  `created`,
  `created_by`,
  `modified`,
  `modified_by`,
  `weight`,
  `length`,
  `width`,
  `height`
)
SELECT
  `id`,
  `id`,
  `sku`,
  `gtin`,
  `stock_quantity`,
  0,
  `reserved_quantity`,
  `sold_quantity`,
  `is_in_stock`,
  `created`,
  `created_by`,
  `modified`,
  `modified_by`,
  `weight`,
  `length`,
  `width`,
  `height`
FROM `#__fdshop_products_old`;


-- ========================================================
-- 4) #__fdshop_product_prices -> neuer Sollstand
--    Alte Tabelle sichern, neue Tabelle anlegen, Daten übernehmen
-- ========================================================

RENAME TABLE `#__fdshop_product_prices` TO `#__fdshop_product_prices_old`;

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

INSERT INTO `#__fdshop_product_prices` (
  `id`,
  `product_id`,
  `currency`,
  `calc_price_net`,
  `calc_price_gross`,
  `calc_stock_cost_net`,
  `calc_shipping_cost_net`,
  `calc_other_cost_net`,
  `calc_target_margin`,
  `manu_price_net`,
  `manu_price_gross`,
  `manu_stock_cost_net`,
  `manu_shipping_cost_net`,
  `manu_other_cost_net`,
  `stock_rule_overridden`,
  `stock_rule_value`,
  `shipping_rule_overridden`,
  `shipping_rule_value`,
  `other_rule_overridden`,
  `other_rule_value`,
  `tax_rate`,
  `purchase_price_net`,
  `margin_effective`,
  `created`,
  `created_by`,
  `modified`,
  `modified_by`
)
SELECT
  p.`id`,
  p.`product_id`,
  p.`currency`,
  COALESCE(c.`calc_price_net`, 0.0000),
  COALESCE(c.`calc_price_gross`, 0.0000),
  COALESCE(c.`supplier_cost_net`, 0.0000),
  COALESCE(c.`shipping_cost_net`, 0.0000),
  COALESCE(c.`other_cost_net`, 0.0000),
  COALESCE(c.`target_margin_percent`, 0.0000),
  p.`price_net`,
  p.`price_gross`,
  0.0000,
  0.0000,
  0.0000,
  0,
  0.0000,
  0,
  0.0000,
  0,
  0.0000,
  p.`tax_rate`,
  0.0000,
  0.0000,
  NOW(),
  0,
  NULL,
  NULL
FROM `#__fdshop_product_prices_old` AS p
LEFT JOIN `#__fdshop_product_price_calc` AS c
  ON c.`product_id` = p.`product_id`;


-- ========================================================
-- 5) #__fdshop_price_calc_rules neu anlegen
--     "rule overridden" wird ignoriert
-- ========================================================

CREATE TABLE `#__fdshop_price_calc_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stock_cost_pct` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `shipping_cost_pct` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `other_cost_pct` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `target_margin_pct` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================================
-- 6) #__fdshop_product_prices_research neu anlegen
-- ========================================================

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


-- ========================================================
-- 7) Alte Tabelle #__fdshop_product_price_calc entfernen
-- ========================================================

DROP TABLE `#__fdshop_product_price_calc`;


-- ========================================================
-- 8) Alte Zwischentabellen entfernen
-- ========================================================

DROP TABLE `#__fdshop_products_old`;
DROP TABLE `#__fdshop_product_prices_old`;

SET FOREIGN_KEY_CHECKS = 1;