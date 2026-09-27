ALTER TABLE `#__fdshop_products`
  DROP COLUMN `meta_keywords`,
  ADD COLUMN `meta_product_type` VARCHAR(50) NOT NULL DEFAULT '' AFTER `meta_title`;

ALTER TABLE `#__fdshop_manufacturers`
  DROP COLUMN `meta_keywords`;
