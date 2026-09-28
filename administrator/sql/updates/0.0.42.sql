CREATE TABLE IF NOT EXISTS `#__fdshop_payment_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_token` CHAR(36) NOT NULL,
  `submission_id` CHAR(36) NOT NULL,
  `user_id` INT NOT NULL,
  `provider` VARCHAR(32) NOT NULL,
  `payment_method_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(32) NOT NULL,
  `snapshot_json` MEDIUMTEXT NOT NULL,
  `amount_minor` BIGINT NOT NULL,
  `currency` CHAR(3) NOT NULL,
  `provider_order_id` VARCHAR(64) NULL,
  `order_id` BIGINT UNSIGNED NULL,
  `expires_at` DATETIME NOT NULL,
  `reservation_released_at` DATETIME NULL,
  `last_error` VARCHAR(1000) NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_payment_session_token` (`session_token`),
  UNIQUE KEY `uk_fdshop_payment_submission` (`submission_id`),
  UNIQUE KEY `uk_fdshop_payment_provider_order` (`provider`,`provider_order_id`),
  KEY `idx_fdshop_payment_session_user_status` (`user_id`,`status`),
  KEY `idx_fdshop_payment_session_expiry` (`status`,`expires_at`),
  CONSTRAINT `fk_fdshop_payment_session_method` FOREIGN KEY (`payment_method_id`) REFERENCES `#__fdshop_payment_methods` (`id`),
  CONSTRAINT `fk_fdshop_payment_session_order` FOREIGN KEY (`order_id`) REFERENCES `#__fdshop_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__fdshop_payment_reservations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_session_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `quantity` DECIMAL(12,3) NOT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'reserved',
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_payment_reservation` (`payment_session_id`,`product_id`),
  KEY `idx_fdshop_payment_reservation_product` (`product_id`,`status`),
  CONSTRAINT `fk_fdshop_payment_reservation_session` FOREIGN KEY (`payment_session_id`) REFERENCES `#__fdshop_payment_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fdshop_payment_reservation_product` FOREIGN KEY (`product_id`) REFERENCES `#__fdshop_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `#__fdshop_config` ADD COLUMN `paypal_reservation_minutes` INT UNSIGNED NOT NULL DEFAULT 10 AFTER `require_terms_checkbox`;

CREATE TABLE IF NOT EXISTS `#__fdshop_payment_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_session_id` BIGINT UNSIGNED NOT NULL,
  `provider` VARCHAR(32) NOT NULL,
  `provider_order_id` VARCHAR(64) NOT NULL,
  `capture_id` VARCHAR(64) NULL,
  `provider_status` VARCHAR(32) NOT NULL,
  `amount_minor` BIGINT NOT NULL,
  `currency` CHAR(3) NOT NULL,
  `captured_at` DATETIME NULL,
  `finalized_at` DATETIME NULL,
  `retry_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_error` VARCHAR(1000) NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_payment_transaction_order` (`provider`,`provider_order_id`),
  UNIQUE KEY `uk_fdshop_payment_capture` (`provider`,`capture_id`),
  KEY `idx_fdshop_payment_transaction_session` (`payment_session_id`),
  CONSTRAINT `fk_fdshop_payment_transaction_session` FOREIGN KEY (`payment_session_id`) REFERENCES `#__fdshop_payment_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
