ALTER TABLE `#__fdshop_config`
  ADD COLUMN `search_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `katalog_active`,
  ADD COLUMN `search_suggestion_limit` TINYINT UNSIGNED NOT NULL DEFAULT 8 AFTER `search_active`;
