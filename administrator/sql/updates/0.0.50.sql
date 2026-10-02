CREATE TABLE IF NOT EXISTS `#__fdshop_cart_continuations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `token_hash` CHAR(64) NOT NULL,
  `guest_session_id` VARCHAR(255) NOT NULL,
  `intent` VARCHAR(20) NOT NULL DEFAULT 'account',
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `shipment_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `payment_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `coupon_code` VARCHAR(100) NOT NULL DEFAULT '',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `expires_at` DATETIME NOT NULL,
  `consumed_at` DATETIME NULL DEFAULT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_cart_continuation_token` (`token_hash`),
  KEY `idx_fdshop_cart_continuation_guest` (`guest_session_id`),
  KEY `idx_fdshop_cart_continuation_user_status` (`user_id`, `status`),
  KEY `idx_fdshop_cart_continuation_expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
