CREATE TABLE IF NOT EXISTS `#__fdshop_filter_category_map` (
  `filter_id` BIGINT UNSIGNED NOT NULL,
  `category_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`filter_id`, `category_id`),
  KEY `idx_fdshop_filter_category_category` (`category_id`),
  CONSTRAINT `fk_fdshop_filter_category_filter` FOREIGN KEY (`filter_id`) REFERENCES `#__fdshop_filters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fdshop_filter_category_category` FOREIGN KEY (`category_id`) REFERENCES `#__fdshop_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__fdshop_filter_range_category_map` (
  `range_id` BIGINT UNSIGNED NOT NULL,
  `category_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`range_id`, `category_id`),
  KEY `idx_fdshop_filter_range_category_category` (`category_id`),
  CONSTRAINT `fk_fdshop_filter_range_category_range` FOREIGN KEY (`range_id`) REFERENCES `#__fdshop_filter_ranges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fdshop_filter_range_category_category` FOREIGN KEY (`category_id`) REFERENCES `#__fdshop_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__fdshop_filter_options` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filter_id` BIGINT UNSIGNED NOT NULL,
  `option_key` VARCHAR(64) NOT NULL,
  `label` VARCHAR(100) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_filter_options_key` (`filter_id`, `option_key`),
  KEY `idx_fdshop_filter_options_active_ordering` (`filter_id`, `is_active`, `ordering`),
  CONSTRAINT `fk_fdshop_filter_options_filter` FOREIGN KEY (`filter_id`) REFERENCES `#__fdshop_filters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__fdshop_filter_option_category_map` (
  `option_id` BIGINT UNSIGNED NOT NULL,
  `category_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`option_id`, `category_id`),
  KEY `idx_fdshop_filter_option_category_category` (`category_id`),
  CONSTRAINT `fk_fdshop_filter_option_category_option` FOREIGN KEY (`option_id`) REFERENCES `#__fdshop_filter_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fdshop_filter_option_category_category` FOREIGN KEY (`category_id`) REFERENCES `#__fdshop_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__fdshop_product_filter_option_map` (
  `product_id` BIGINT UNSIGNED NOT NULL,
  `option_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`product_id`, `option_id`),
  KEY `idx_fdshop_product_filter_option_option` (`option_id`, `product_id`),
  CONSTRAINT `fk_fdshop_product_filter_option_product` FOREIGN KEY (`product_id`) REFERENCES `#__fdshop_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fdshop_product_filter_option_option` FOREIGN KEY (`option_id`) REFERENCES `#__fdshop_filter_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__fdshop_filters` (`filter_key`, `label`, `is_active`, `ordering`) VALUES
('firing_type', 'Abschuss', 1, 60), ('product_type', 'Art', 1, 70)
ON DUPLICATE KEY UPDATE `filter_key` = VALUES(`filter_key`);

INSERT INTO `#__fdshop_filter_options` (`filter_id`, `option_key`, `label`, `is_active`, `ordering`)
SELECT f.id, seed.option_key, seed.label, 1, seed.ordering
FROM `#__fdshop_filters` f JOIN (
  SELECT 'firing_type' filter_key, 'straight' option_key, 'Gerade' label, 10 ordering
  UNION ALL SELECT 'firing_type', 'fanned', 'Gefächert', 20
  UNION ALL SELECT 'product_type', 'ground_firework', 'Bodenfeuerwerk', 10
  UNION ALL SELECT 'product_type', 'firebird', 'Feuervögel', 20
  UNION ALL SELECT 'product_type', 'roman_candle', 'Römische Lichter', 30
  UNION ALL SELECT 'product_type', 'fountain_volcano', 'Vulkane & Fontänen', 40
) seed ON seed.filter_key = f.filter_key
LEFT JOIN `#__fdshop_filter_options` o ON o.filter_id = f.id AND o.option_key = seed.option_key
WHERE o.id IS NULL;
