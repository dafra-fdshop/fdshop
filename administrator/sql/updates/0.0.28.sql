ALTER TABLE `#__fdshop_orders`
  ADD COLUMN `customer_name` VARCHAR(255) NULL AFTER `has_bundle`,
  ADD COLUMN `customer_email` VARCHAR(255) NULL AFTER `customer_name`,
  ADD COLUMN `payment_method_name` VARCHAR(255) NULL AFTER `customer_email`,
  ADD COLUMN `payment_fee` DECIMAL(12,4) NULL AFTER `payment_method_name`,
  ADD COLUMN `shipment_name` VARCHAR(255) NULL AFTER `payment_fee`,
  ADD COLUMN `shipment_fee` DECIMAL(12,4) NULL AFTER `shipment_name`,
  ADD COLUMN `subtotal` DECIMAL(12,4) NULL AFTER `shipment_fee`,
  ADD COLUMN `coupon_code` VARCHAR(64) NULL AFTER `subtotal`,
  ADD COLUMN `coupon_discount` DECIMAL(12,4) NULL AFTER `coupon_code`,
  ADD COLUMN `order_note` TEXT NULL AFTER `coupon_discount`,
  ADD COLUMN `terms_required` TINYINT(1) NULL AFTER `order_note`,
  ADD COLUMN `terms_accepted` TINYINT(1) NULL AFTER `terms_required`,
  ADD COLUMN `terms_accepted_at` DATETIME NULL AFTER `terms_accepted`,
  ADD COLUMN `stock_state` VARCHAR(16) NULL AFTER `terms_accepted_at`,
  ADD COLUMN `submission_id` CHAR(36) NULL AFTER `stock_state`,
  ADD COLUMN `mail_warning` TEXT NULL AFTER `submission_id`,
  ADD UNIQUE KEY `uk_fdshop_orders_submission_id` (`submission_id`);

ALTER TABLE `#__fdshop_order_items`
  ADD COLUMN `unit_variant` VARCHAR(16) NOT NULL DEFAULT 'piece' AFTER `quantity`,
  ADD COLUMN `unit_type_snapshot` VARCHAR(64) NOT NULL DEFAULT 'Stück' AFTER `unit_variant`,
  ADD COLUMN `unit_quantity_snapshot` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `unit_type_snapshot`,
  ADD COLUMN `physical_quantity` DECIMAL(12,3) NOT NULL DEFAULT 1.000 AFTER `unit_quantity_snapshot`;

CREATE TABLE IF NOT EXISTS `#__fdshop_order_stock_allocations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `physical_quantity` DECIMAL(12,3) NOT NULL,
  `stock_state` VARCHAR(16) NOT NULL DEFAULT 'none',
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_order_stock_order_product` (`order_id`, `product_id`),
  KEY `idx_fdshop_order_stock_order_id` (`order_id`),
  KEY `idx_fdshop_order_stock_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
