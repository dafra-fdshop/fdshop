CREATE TABLE IF NOT EXISTS `#__fdshop_filters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filter_key` VARCHAR(32) NOT NULL,
  `label` VARCHAR(100) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_fdshop_filters_key` (`filter_key`),
  KEY `idx_fdshop_filters_active_ordering` (`is_active`, `ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__fdshop_filter_ranges` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filter_id` BIGINT UNSIGNED NOT NULL,
  `label` VARCHAR(100) NOT NULL,
  `value_from` DECIMAL(12,3) NULL DEFAULT NULL,
  `value_to` DECIMAL(12,3) NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_fdshop_filter_ranges_filter_active_ordering` (`filter_id`, `is_active`, `ordering`),
  CONSTRAINT `fk_fdshop_filter_ranges_filter` FOREIGN KEY (`filter_id`) REFERENCES `#__fdshop_filters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__fdshop_filters` (`filter_key`, `label`, `is_active`, `ordering`) VALUES
('manufacturer', 'Hersteller', 1, 10), ('availability', 'Verfügbarkeit', 1, 20),
('duration', 'Brenndauer', 1, 30), ('caliber', 'Kaliber', 1, 40), ('nem', 'NEM', 1, 50)
ON DUPLICATE KEY UPDATE `filter_key` = VALUES(`filter_key`);

INSERT INTO `#__fdshop_filter_ranges` (`filter_id`, `label`, `value_from`, `value_to`, `is_active`, `ordering`)
SELECT f.id, seed.label, seed.value_from, seed.value_to, 1, seed.ordering FROM `#__fdshop_filters` f JOIN (
  SELECT 'duration' filter_key, '0 bis 20 s' label, 0.000 value_from, 20.000 value_to, 10 ordering
  UNION ALL SELECT 'duration', '20 bis 40 s', 20.000, 40.000, 20 UNION ALL SELECT 'duration', 'mehr als 40 s', 40.000, NULL, 30
  UNION ALL SELECT 'caliber', '0 bis 20 mm', 0.000, 20.000, 10 UNION ALL SELECT 'caliber', '20 bis 25 mm', 20.000, 25.000, 20 UNION ALL SELECT 'caliber', '25 bis 30 mm', 25.000, 30.000, 30
  UNION ALL SELECT 'nem', '0 bis 200 g', 0.000, 200.000, 10 UNION ALL SELECT 'nem', '200 bis 400 g', 200.000, 400.000, 20 UNION ALL SELECT 'nem', '400 bis 500 g', 400.000, 500.000, 30
) seed ON seed.filter_key=f.filter_key WHERE NOT EXISTS (SELECT 1 FROM `#__fdshop_filter_ranges` r WHERE r.filter_id=f.id);
