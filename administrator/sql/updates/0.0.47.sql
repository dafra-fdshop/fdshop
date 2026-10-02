ALTER TABLE `#__fdshop_config`
  ADD COLUMN `favorites_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `search_suggestion_limit`,
  ADD COLUMN `favorites_max_custom_lists` TINYINT UNSIGNED NOT NULL DEFAULT 3 AFTER `favorites_active`,
  ADD COLUMN `favorites_max_products` SMALLINT UNSIGNED NOT NULL DEFAULT 100 AFTER `favorites_max_custom_lists`;

CREATE TABLE IF NOT EXISTS `#__fdshop_favorite_lists` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fdshop_favorite_list_name` (`user_id`,`name`),
  KEY `idx_fdshop_favorite_list_owner` (`user_id`,`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__fdshop_favorite_items` (
  `list_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`list_id`,`product_id`),
  KEY `idx_fdshop_favorite_product` (`product_id`,`list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
