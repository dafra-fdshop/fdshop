ALTER TABLE `#__fdshop_products_details`
  ADD COLUMN `bundle_eligible` TINYINT(1) NOT NULL DEFAULT 0 AFTER `gtin`,
  ADD INDEX `idx_fdshop_products_details_bundle_eligible` (`bundle_eligible`);

UPDATE `#__fdshop_products_details` AS `d`
INNER JOIN `#__fdshop_products` AS `p` ON `p`.`id` = `d`.`product_id`
SET `d`.`bundle_eligible` = `p`.`ribbon_bundle`;

UPDATE `#__fdshop_products` AS `p`
SET `p`.`ribbon_bundle` = CASE
  WHEN EXISTS (
    SELECT 1
    FROM `#__fdshop_bundle_items` AS `bi`
    WHERE `bi`.`product_id` = `p`.`id`
  ) THEN 1
  ELSE 0
END;
