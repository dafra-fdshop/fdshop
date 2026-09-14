ALTER TABLE `#__fdshop_products_details`
  ADD COLUMN `unit_quantity` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `height`,
  ADD COLUMN `unit_discount_type` VARCHAR(20) NOT NULL DEFAULT 'none' AFTER `unit_quantity`,
  ADD COLUMN `unit_discount_value` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `unit_discount_type`;

UPDATE `#__fdshop_products_details` AS d
INNER JOIN `#__fdshop_products` AS p ON p.id = d.product_id
SET d.unit_quantity = CASE
  WHEN p.unit_type IN ('Display', 'Schinken', 'VE') AND p.unit_quantity >= 2 THEN p.unit_quantity
  ELSE 1 END;

UPDATE `#__fdshop_products`
SET `unit_type` = 'Stück'
WHERE `unit_type` NOT IN ('Stück', 'Display', 'Schinken', 'VE')
   OR (`unit_type` IN ('Display', 'Schinken', 'VE') AND `unit_quantity` < 2);

ALTER TABLE `#__fdshop_products` DROP COLUMN `unit_quantity`;

ALTER TABLE `#__fdshop_cart`
  ADD COLUMN `unit_variant` VARCHAR(20) NOT NULL DEFAULT 'piece' AFTER `currency`,
  ADD COLUMN `unit_type_snapshot` VARCHAR(100) NOT NULL DEFAULT 'Stück' AFTER `unit_variant`,
  ADD COLUMN `unit_quantity_snapshot` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `unit_type_snapshot`,
  ADD KEY `idx_fdshop_cart_variant` (`product_id`, `unit_variant`, `unit_type_snapshot`, `unit_quantity_snapshot`);

UPDATE `#__fdshop_cart`
SET `unit_variant` = 'piece', `unit_type_snapshot` = 'Stück', `unit_quantity_snapshot` = 1;
