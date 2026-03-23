-- FDShop update 0.0.3
-- Erweiterung Hersteller- und Produktfelder laut Auftrag DB-KI.
-- Keine Feldumbenennungen, keine Löschungen, keine Architekturänderung.

ALTER TABLE `#__fdshop_manufacturers`
  ADD COLUMN `description` TEXT NULL AFTER `alias`,
  ADD COLUMN `meta_title` VARCHAR(255) NOT NULL DEFAULT '' AFTER `description`,
  ADD COLUMN `meta_keywords` TEXT NULL AFTER `meta_title`,
  ADD COLUMN `meta_description` TEXT NULL AFTER `meta_keywords`;

ALTER TABLE `#__fdshop_products`
  ADD COLUMN `meta_title` VARCHAR(255) NOT NULL DEFAULT '' AFTER `modified_by`,
  ADD COLUMN `meta_keywords` TEXT NULL AFTER `meta_title`,
  ADD COLUMN `meta_description` TEXT NULL AFTER `meta_keywords`,

  ADD COLUMN `discount_price` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `meta_description`,
  ADD COLUMN `discount_active` TINYINT(1) NOT NULL DEFAULT 0 AFTER `discount_price`,

  ADD COLUMN `is_in_stock` TINYINT(1) NOT NULL DEFAULT 1 AFTER `discount_active`,
  ADD COLUMN `available_from` DATETIME NULL DEFAULT NULL AFTER `is_in_stock`,
  ADD COLUMN `sold_quantity` INT NOT NULL DEFAULT 0 AFTER `available_from`,

  ADD COLUMN `unit_type` VARCHAR(255) NOT NULL DEFAULT '' AFTER `sold_quantity`,
  ADD COLUMN `unit_quantity` INT NOT NULL DEFAULT 0 AFTER `unit_type`,

  ADD COLUMN `nem` FLOAT NOT NULL DEFAULT 0 AFTER `unit_quantity`,
  ADD COLUMN `shot_count` INT NOT NULL DEFAULT 0 AFTER `nem`,
  ADD COLUMN `caliber` VARCHAR(255) NOT NULL DEFAULT '' AFTER `shot_count`,
  ADD COLUMN `burn_time` VARCHAR(255) NOT NULL DEFAULT '' AFTER `caliber`,
  ADD COLUMN `rise_height` VARCHAR(255) NOT NULL DEFAULT '' AFTER `burn_time`,

  ADD COLUMN `weight_kg` FLOAT NOT NULL DEFAULT 0 AFTER `rise_height`,
  ADD COLUMN `length_cm` FLOAT NOT NULL DEFAULT 0 AFTER `weight_kg`,
  ADD COLUMN `width_cm` FLOAT NOT NULL DEFAULT 0 AFTER `length_cm`,
  ADD COLUMN `height_cm` FLOAT NOT NULL DEFAULT 0 AFTER `width_cm`,

  ADD COLUMN `purchase_price` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `height_cm`,
  ADD COLUMN `sale_price` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `purchase_price`;