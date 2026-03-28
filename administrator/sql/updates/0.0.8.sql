-- FDShop update 0.0.8
-- Add state field to products and migrate publish status

ALTER TABLE `#__fdshop_products`
  ADD COLUMN `state` TINYINT NOT NULL DEFAULT 1 AFTER `is_active`,
  ADD KEY `idx_fdshop_products_state` (`state`);

UPDATE `#__fdshop_products`
SET `state` = CASE
  WHEN `is_active` = 1 THEN 1
  ELSE 0
END;