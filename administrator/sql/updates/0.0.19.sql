-- FDShop update 0.0.19
-- Modernize immutable order snapshots and consolidate order history.
-- Empty/zero values on legacy rows mean "historically unknown"; current product data is never backfilled.

ALTER TABLE `#__fdshop_order_items`
  ADD COLUMN `gtin` VARCHAR(64) NOT NULL DEFAULT '' AFTER `sku`,
  ADD COLUMN `manufacturer_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `gtin`,
  ADD COLUMN `regular_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `quantity`,
  ADD COLUMN `discount_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `regular_price_gross`,
  ADD COLUMN `tax_rate` DECIMAL(7,4) NOT NULL DEFAULT 0.0000 AFTER `unit_price_gross`,
  ADD COLUMN `line_total_net` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `tax_rate`,
  ADD COLUMN `line_total_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `line_total_net`,
  ADD COLUMN `currency` CHAR(3) NOT NULL DEFAULT 'EUR' AFTER `line_total_gross`,
  ADD COLUMN `is_removed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `currency`;

UPDATE `#__fdshop_order_items`
SET `line_total_net` = ROUND(`quantity` * `unit_price_net`, 4),
    `line_total_gross` = ROUND(`quantity` * `unit_price_gross`, 4)
WHERE `line_total_net` = 0.0000 AND `line_total_gross` = 0.0000;

ALTER TABLE `#__fdshop_order_bundles`
  ADD COLUMN `is_removed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `total_gross`;

ALTER TABLE `#__fdshop_order_bundle_items`
  ADD COLUMN `gtin` VARCHAR(64) NOT NULL DEFAULT '' AFTER `sku`,
  ADD COLUMN `manufacturer_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `gtin`,
  ADD COLUMN `regular_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `quantity`,
  ADD COLUMN `discount_price_gross` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `regular_price_gross`,
  ADD COLUMN `tax_rate` DECIMAL(7,4) NOT NULL DEFAULT 0.0000 AFTER `unit_price_gross`,
  ADD COLUMN `currency` CHAR(3) NOT NULL DEFAULT 'EUR' AFTER `total_gross`,
  ADD COLUMN `is_removed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `currency`;

ALTER TABLE `#__fdshop_order_history`
  MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  MODIFY COLUMN `order_id` BIGINT UNSIGNED NOT NULL,
  MODIFY COLUMN `reference_id` BIGINT UNSIGNED NULL;

ALTER TABLE `#__fdshop_order_status_history`
  MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  MODIFY COLUMN `order_id` BIGINT UNSIGNED NOT NULL;

ALTER TABLE `#__fdshop_order_items`
  ADD INDEX `idx_fdshop_order_items_is_removed` (`is_removed`);
ALTER TABLE `#__fdshop_order_bundles`
  ADD INDEX `idx_fdshop_order_bundles_is_removed` (`is_removed`);
ALTER TABLE `#__fdshop_order_bundle_items`
  ADD INDEX `idx_fdshop_order_bundle_items_is_removed` (`is_removed`);

INSERT INTO `#__fdshop_order_history`
  (`order_id`,`event_type`,`event_title`,`event_text`,`reference_type`,`reference_id`,`is_system_event`,`created`,`created_by`)
SELECT `order_id`, 'legacy_history', `history_type`,
       CONCAT_WS('\n', NULLIF(`note`,''), CONCAT('Altwert: ',COALESCE(`old_value`,'')), CONCAT('Neuwert: ',COALESCE(`new_value`,''))),
       'legacy_orders_history', `id`, 1, `created`, `created_by`
FROM `#__fdshop_orders_history`;

DROP TABLE IF EXISTS `#__fdshop_orders_history`;
