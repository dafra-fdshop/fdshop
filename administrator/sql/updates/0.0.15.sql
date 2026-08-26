-- FDShop update 0.0.15
-- Rename shipment and payment status fields to published

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `#__fdshop_shipments`
  DROP KEY `idx_fdshop_shipments_is_published`,
  CHANGE COLUMN `is_published` `published` TINYINT(1) NOT NULL DEFAULT 1,
  ADD KEY `idx_fdshop_shipments_published` (`published`);

ALTER TABLE `#__fdshop_payment_methods`
  DROP KEY `idx_fdshop_payment_methods_is_published`,
  CHANGE COLUMN `is_published` `published` TINYINT(1) NOT NULL DEFAULT 1,
  ADD KEY `idx_fdshop_payment_methods_published` (`published`);

SET FOREIGN_KEY_CHECKS = 1;
