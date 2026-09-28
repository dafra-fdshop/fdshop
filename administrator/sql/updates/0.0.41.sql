CREATE TABLE IF NOT EXISTS `#__fdshop_product_watchlist` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` DATETIME NULL DEFAULT NULL,
  `notified_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_watchlist_product_user` (`product_id`, `user_id`),
  KEY `idx_fdshop_watchlist_status_product` (`status`, `product_id`),
  KEY `idx_fdshop_watchlist_user_status` (`user_id`, `status`),
  CONSTRAINT `fk_fdshop_watchlist_product` FOREIGN KEY (`product_id`) REFERENCES `#__fdshop_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fdshop_watchlist_user` FOREIGN KEY (`user_id`) REFERENCES `#__users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
