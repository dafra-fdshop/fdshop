-- FDShop update 0.0.14
-- Move catalog mode field from products to config table

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `#__fdshop_config`
  ADD COLUMN `katalog_active` TINYINT(1) NOT NULL DEFAULT 0 AFTER `require_terms_checkbox`;

INSERT INTO `#__fdshop_config` (
  `id`,
  `katalog_active`
)
SELECT
  1,
  0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1
  FROM `#__fdshop_config`
  WHERE `id` = 1
);

ALTER TABLE `#__fdshop_products`
  DROP COLUMN `katalog_active`;

SET FOREIGN_KEY_CHECKS = 1;
