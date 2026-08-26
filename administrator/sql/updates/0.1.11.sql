-- FDShop update 0.1.11
-- Add supplier master-data administration

CREATE TABLE IF NOT EXISTS `#__fdshop_suppliers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_name` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(191) NOT NULL,
  `contact_name` VARCHAR(255) NOT NULL DEFAULT '',
  `email` VARCHAR(255) NOT NULL DEFAULT '',
  `phone` VARCHAR(100) NOT NULL DEFAULT '',
  `website` VARCHAR(2048) NOT NULL DEFAULT '',
  `customer_number` VARCHAR(100) NOT NULL DEFAULT '',
  `note` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_suppliers_alias` (`alias`),
  KEY `idx_fdshop_suppliers_name` (`supplier_name`),
  KEY `idx_fdshop_suppliers_customer_number` (`customer_number`),
  KEY `idx_fdshop_suppliers_is_active` (`is_active`),
  KEY `idx_fdshop_suppliers_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
