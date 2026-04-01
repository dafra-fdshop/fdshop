-- FDShop update 0.0.9
-- Add order status tables and status id basis for orders

ALTER TABLE `#__fdshop_orders`
  ADD COLUMN `order_status_id` INT UNSIGNED NOT NULL AFTER `order_status`,
  ADD KEY `idx_fdshop_orders_order_status_id` (`order_status_id`);

CREATE TABLE IF NOT EXISTS `#__fdshop_order_statuses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `status_code` VARCHAR(32) NOT NULL,
  `status_name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,

  `notify_seller` TINYINT(1) NOT NULL DEFAULT 0,
  `notify_buyer` TINYINT(1) NOT NULL DEFAULT 0,
  `create_invoice` TINYINT(1) NOT NULL DEFAULT 0,

  `stock_action` VARCHAR(32) NOT NULL DEFAULT 'none',

  `seller_email_mode` VARCHAR(32) NOT NULL DEFAULT 'config',
  `seller_email_address` VARCHAR(255) NULL,

  `buyer_email_mode` VARCHAR(32) NOT NULL DEFAULT 'account',

  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,

  `created` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fdshop_order_statuses_status_code` (`status_code`),
  KEY `idx_fdshop_order_statuses_is_active` (`is_active`),
  KEY `idx_fdshop_order_statuses_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__fdshop_order_status_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `old_status_id` INT UNSIGNED NULL,
  `new_status_id` INT UNSIGNED NOT NULL,

  `comment` TEXT NULL,
  `is_system_change` TINYINT(1) NOT NULL DEFAULT 0,

  `changed_at` DATETIME NOT NULL,
  `changed_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_order_status_history_order_id` (`order_id`),
  KEY `idx_fdshop_order_status_history_old_status_id` (`old_status_id`),
  KEY `idx_fdshop_order_status_history_new_status_id` (`new_status_id`),
  KEY `idx_fdshop_order_status_history_changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__fdshop_order_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,

  `event_type` VARCHAR(50) NOT NULL,
  `event_title` VARCHAR(255) NOT NULL,
  `event_text` TEXT NULL,

  `reference_type` VARCHAR(50) NULL,
  `reference_id` INT UNSIGNED NULL,

  `is_system_event` TINYINT(1) NOT NULL DEFAULT 0,

  `created` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_fdshop_order_history_order_id` (`order_id`),
  KEY `idx_fdshop_order_history_event_type` (`event_type`),
  KEY `idx_fdshop_order_history_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__fdshop_order_statuses` (
  `id`,
  `status_code`,
  `status_name`,
  `description`,
  `notify_seller`,
  `notify_buyer`,
  `create_invoice`,
  `stock_action`,
  `seller_email_mode`,
  `seller_email_address`,
  `buyer_email_mode`,
  `is_active`,
  `ordering`,
  `created`,
  `created_by`,
  `modified`,
  `modified_by`
)
SELECT
  1,
  'ordered',
  'Vom Kunden bestellt',
  NULL,
  1,
  1,
  0,
  'reserve',
  'config',
  NULL,
  'account',
  1,
  1,
  CURRENT_TIMESTAMP,
  0,
  NULL,
  0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `#__fdshop_order_statuses` WHERE `status_code` = 'ordered'
);

INSERT INTO `#__fdshop_order_statuses` (
  `id`,
  `status_code`,
  `status_name`,
  `description`,
  `notify_seller`,
  `notify_buyer`,
  `create_invoice`,
  `stock_action`,
  `seller_email_mode`,
  `seller_email_address`,
  `buyer_email_mode`,
  `is_active`,
  `ordering`,
  `created`,
  `created_by`,
  `modified`,
  `modified_by`
)
SELECT
  2,
  'paid',
  'Zahlung erhalten',
  NULL,
  1,
  1,
  1,
  'reserve',
  'config',
  NULL,
  'account',
  1,
  2,
  CURRENT_TIMESTAMP,
  0,
  NULL,
  0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `#__fdshop_order_statuses` WHERE `status_code` = 'paid'
);

INSERT INTO `#__fdshop_order_statuses` (
  `id`,
  `status_code`,
  `status_name`,
  `description`,
  `notify_seller`,
  `notify_buyer`,
  `create_invoice`,
  `stock_action`,
  `seller_email_mode`,
  `seller_email_address`,
  `buyer_email_mode`,
  `is_active`,
  `ordering`,
  `created`,
  `created_by`,
  `modified`,
  `modified_by`
)
SELECT
  3,
  'packed',
  'Bestellung gepackt',
  NULL,
  0,
  1,
  0,
  'none',
  'config',
  NULL,
  'account',
  1,
  3,
  CURRENT_TIMESTAMP,
  0,
  NULL,
  0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `#__fdshop_order_statuses` WHERE `status_code` = 'packed'
);

INSERT INTO `#__fdshop_order_statuses` (
  `id`,
  `status_code`,
  `status_name`,
  `description`,
  `notify_seller`,
  `notify_buyer`,
  `create_invoice`,
  `stock_action`,
  `seller_email_mode`,
  `seller_email_address`,
  `buyer_email_mode`,
  `is_active`,
  `ordering`,
  `created`,
  `created_by`,
  `modified`,
  `modified_by`
)
SELECT
  4,
  'shipped',
  'Versendet',
  NULL,
  0,
  1,
  0,
  'deduct',
  'config',
  NULL,
  'account',
  1,
  4,
  CURRENT_TIMESTAMP,
  0,
  NULL,
  0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `#__fdshop_order_statuses` WHERE `status_code` = 'shipped'
);

INSERT INTO `#__fdshop_order_statuses` (
  `id`,
  `status_code`,
  `status_name`,
  `description`,
  `notify_seller`,
  `notify_buyer`,
  `create_invoice`,
  `stock_action`,
  `seller_email_mode`,
  `seller_email_address`,
  `buyer_email_mode`,
  `is_active`,
  `ordering`,
  `created`,
  `created_by`,
  `modified`,
  `modified_by`
)
SELECT
  5,
  'cancelled',
  'Storniert',
  NULL,
  1,
  1,
  0,
  'available',
  'config',
  NULL,
  'account',
  1,
  5,
  CURRENT_TIMESTAMP,
  0,
  NULL,
  0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `#__fdshop_order_statuses` WHERE `status_code` = 'cancelled'
);

INSERT INTO `#__fdshop_order_statuses` (
  `id`,
  `status_code`,
  `status_name`,
  `description`,
  `notify_seller`,
  `notify_buyer`,
  `create_invoice`,
  `stock_action`,
  `seller_email_mode`,
  `seller_email_address`,
  `buyer_email_mode`,
  `is_active`,
  `ordering`,
  `created`,
  `created_by`,
  `modified`,
  `modified_by`
)
SELECT
  6,
  'completed',
  'Abgeschlossen',
  NULL,
  0,
  1,
  0,
  'deduct',
  'config',
  NULL,
  'account',
  1,
  6,
  CURRENT_TIMESTAMP,
  0,
  NULL,
  0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `#__fdshop_order_statuses` WHERE `status_code` = 'completed'
);

UPDATE `#__fdshop_orders`
SET `order_status_id` = CASE `order_status`
  WHEN 'ordered' THEN 1
  WHEN 'paid' THEN 2
  WHEN 'packed' THEN 3
  WHEN 'shipped' THEN 4
  WHEN 'cancelled' THEN 5
  WHEN 'completed' THEN 6
  ELSE 1
END;