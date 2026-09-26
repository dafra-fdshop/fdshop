CREATE TABLE IF NOT EXISTS `#__fdshop_order_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `change_id` BIGINT UNSIGNED NULL,
  `document_type` VARCHAR(32) NOT NULL DEFAULT 'confirmation',
  `version_no` INT UNSIGNED NOT NULL DEFAULT 0,
  `filename` VARCHAR(255) NOT NULL,
  `sha256` CHAR(64) NOT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fdshop_order_document_version` (`order_id`,`document_type`,`version_no`),
  UNIQUE KEY `uq_fdshop_order_document_change` (`order_id`,`document_type`,`change_id`),
  KEY `idx_fdshop_order_documents_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
