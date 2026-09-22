ALTER TABLE `#__fdshop_config`
  ADD COLUMN `image_quality_default` TINYINT UNSIGNED NOT NULL DEFAULT 80 AFTER `image_size_default`,
  ADD COLUMN `image_quality_small` TINYINT UNSIGNED NOT NULL DEFAULT 60 AFTER `image_size_small`,
  ADD COLUMN `image_quality_mobile` TINYINT UNSIGNED NOT NULL DEFAULT 70 AFTER `image_size_mobile`;
