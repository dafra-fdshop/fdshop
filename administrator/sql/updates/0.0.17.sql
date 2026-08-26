-- FDShop update 0.0.17
-- Add storage location master-data administration

CREATE TABLE IF NOT EXISTS `#__fdshop_storage_locations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `location_name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(255) NOT NULL DEFAULT '',
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_storage_locations_is_active` (`is_active`),
  KEY `idx_fdshop_storage_locations_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
