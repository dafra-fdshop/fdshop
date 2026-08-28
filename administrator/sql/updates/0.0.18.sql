-- FDShop update 0.0.18
-- Add a dedicated product trash status without changing is_active

ALTER TABLE `#__fdshop_products`
  ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`,
  ADD INDEX `idx_fdshop_products_is_deleted` (`is_deleted`);
