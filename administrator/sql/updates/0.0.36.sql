INSERT INTO `#__fdshop_buyer_groups` (`group_name`,`alias`,`is_active`,`ordering`,`created_by`)
SELECT 'Ohne Schein (Standard)','standard',1,10,0 WHERE NOT EXISTS (SELECT 1 FROM `#__fdshop_buyer_groups` WHERE `alias`='standard');
INSERT INTO `#__fdshop_buyer_groups` (`group_name`,`alias`,`is_active`,`ordering`,`created_by`)
SELECT 'Mit Schein (F3-Berechtigung)','permit_holder',1,20,0 WHERE NOT EXISTS (SELECT 1 FROM `#__fdshop_buyer_groups` WHERE `alias`='permit_holder');
ALTER TABLE `#__fdshop_products` MODIFY `buyer_group_id` BIGINT UNSIGNED NOT NULL DEFAULT 0;
UPDATE `#__fdshop_buyer_groups` SET `group_name`='Ohne Schein (Standard)',`is_active`=1,`ordering`=10 WHERE `alias`='standard';
UPDATE `#__fdshop_buyer_groups` SET `group_name`='Mit Schein (F3-Berechtigung)',`is_active`=1,`ordering`=20 WHERE `alias`='permit_holder';
UPDATE `#__fdshop_buyer_groups` SET `is_active`=0 WHERE `alias` NOT IN ('standard','permit_holder');
UPDATE `#__fdshop_products` SET `buyer_group_id`=(SELECT `id` FROM `#__fdshop_buyer_groups` WHERE `alias`='standard' LIMIT 1);
DELETE FROM `#__fdshop_product_buyer_group_map`;
INSERT INTO `#__fdshop_product_buyer_group_map` (`product_id`,`buyer_group_id`) SELECT `id`,`buyer_group_id` FROM `#__fdshop_products`;
DELETE m FROM `#__fdshop_user_buyer_group_map` m LEFT JOIN `#__fdshop_buyer_groups` g ON g.id=m.buyer_group_id WHERE g.alias NOT IN ('standard','permit_holder') OR g.alias IS NULL;
INSERT IGNORE INTO `#__fdshop_coupon_buyer_group_map` (`coupon_id`,`buyer_group_id`,`created_by`)
SELECT DISTINCT m.coupon_id,(SELECT id FROM `#__fdshop_buyer_groups` WHERE alias='standard' LIMIT 1),0 FROM `#__fdshop_coupon_buyer_group_map` m JOIN `#__fdshop_buyer_groups` g ON g.id=m.buyer_group_id WHERE g.alias NOT IN ('standard','permit_holder');
DELETE m FROM `#__fdshop_coupon_buyer_group_map` m LEFT JOIN `#__fdshop_buyer_groups` g ON g.id=m.buyer_group_id WHERE g.alias NOT IN ('standard','permit_holder') OR g.alias IS NULL;
