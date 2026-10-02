ALTER TABLE `#__fdshop_config`
  ADD COLUMN `comparison_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `favorites_max_products`,
  ADD COLUMN `comparison_max_products` TINYINT UNSIGNED NOT NULL DEFAULT 4 AFTER `comparison_active`,
  ADD COLUMN `comparison_max_saved_lists` TINYINT UNSIGNED NOT NULL DEFAULT 4 AFTER `comparison_max_products`;
CREATE TABLE IF NOT EXISTS `#__fdshop_comparison_lists` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,`user_id` INT UNSIGNED NOT NULL,`category_id` BIGINT UNSIGNED NOT NULL,`name` VARCHAR(100) NOT NULL,`created` DATETIME NOT NULL,`modified` DATETIME NOT NULL,PRIMARY KEY(`id`),UNIQUE KEY `uq_fdshop_comparison_name` (`user_id`,`name`),KEY `idx_fdshop_comparison_owner` (`user_id`,`modified`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `#__fdshop_comparison_items` (`list_id` INT UNSIGNED NOT NULL,`product_id` BIGINT UNSIGNED NOT NULL,`ordering` TINYINT UNSIGNED NOT NULL DEFAULT 0,PRIMARY KEY(`list_id`,`product_id`),KEY `idx_fdshop_comparison_product` (`product_id`,`list_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
