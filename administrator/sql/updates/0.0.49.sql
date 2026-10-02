ALTER TABLE `#__fdshop_config`
  ADD COLUMN `search_category_active` TINYINT(1) NOT NULL DEFAULT 0 AFTER `search_active`;
