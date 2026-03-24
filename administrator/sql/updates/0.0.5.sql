-- FDShop update 0.0.5
-- Add media table for product image variants

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