SET NAMES utf8mb4;
START TRANSACTION;

UPDATE `__PREFIX__fdshop_config` SET `show_terms_checkbox`=1, `require_terms_checkbox`=1 WHERE `id`=1;
UPDATE `__PREFIX__fdshop_products` SET `min_order_qty`=0, `max_order_qty`=0, `step_order_qty`=0 WHERE `id`=900100;
UPDATE `__PREFIX__fdshop_products` SET `min_order_qty`=2, `max_order_qty`=8, `step_order_qty`=2 WHERE `id`=900105;
UPDATE `__PREFIX__fdshop_shipments` SET `published`=1, `is_default`=0 WHERE `id`=900601;
UPDATE `__PREFIX__fdshop_payment_methods` SET `published`=1, `is_default`=0 WHERE `id`=900611;

DELETE FROM `__PREFIX__user_profiles` WHERE `user_id`=__JOOMLA_USER_ID__ AND `profile_key` LIKE 'fdshop_customer.%';
INSERT INTO `__PREFIX__user_profiles` (`user_id`,`profile_key`,`profile_value`,`ordering`) VALUES
(__JOOMLA_USER_ID__,'fdshop_customer.first_name','"Erika"',100),
(__JOOMLA_USER_ID__,'fdshop_customer.last_name','"Mustermann"',101),
(__JOOMLA_USER_ID__,'fdshop_customer.company','"FDShop Test GmbH"',102),
(__JOOMLA_USER_ID__,'fdshop_customer.street','"Teststraße 12"',103),
(__JOOMLA_USER_ID__,'fdshop_customer.postal_code','"12345"',104),
(__JOOMLA_USER_ID__,'fdshop_customer.city','"Teststadt"',105),
(__JOOMLA_USER_ID__,'fdshop_customer.country','"Deutschland"',106),
(__JOOMLA_USER_ID__,'fdshop_customer.phone','"+49 30 123456"',107);

INSERT INTO `__PREFIX__fdshop_cart` (`id`,`user_id`,`session_id`,`product_id`,`buyer_group_id`,`quantity`,`unit_price_net`,`unit_price_gross`,`currency`,`created`) VALUES
(910000,__JOOMLA_USER_ID__,'',900100,900020,1,16.7983,19.9900,'EUR','2026-01-10 10:00:00'),
(910001,__JOOMLA_USER_ID__,'',900105,900020,2,33.6050,39.9900,'EUR','2026-01-10 10:01:00'),
(910002,__JOOMLA_USER_ID__,'',900107,900020,1,22.6891,27.0000,'EUR','2026-01-10 10:02:00'),
(910003,__JOOMLA_USER_ID__+1000,'',900103,900020,1,19.3277,23.0000,'EUR','2026-01-10 10:03:00');

DELETE FROM `__PREFIX__menu` WHERE `id`=900901 OR (`alias`='warenkorb' AND `link` LIKE 'index.php?option=com_fdshop&view=cart%');
INSERT INTO `__PREFIX__menu`
(`id`,`menutype`,`title`,`alias`,`note`,`path`,`link`,`type`,`published`,`parent_id`,`level`,`component_id`,`checked_out`,`checked_out_time`,`browserNav`,`access`,`img`,`template_style_id`,`params`,`lft`,`rgt`,`home`,`language`,`client_id`,`publish_up`,`publish_down`)
VALUES
(900901,'mainmenu','Warenkorb','warenkorb','','warenkorb','index.php?option=com_fdshop&view=cart','component',1,1,1,__COMPONENT_ID__,0,NULL,0,1,'',0,'{}',__MENU_LFT__,__MENU_RGT__,0,'*',0,NULL,NULL);

DELETE `mm` FROM `__PREFIX__modules_menu` AS `mm` INNER JOIN `__PREFIX__modules` AS `m` ON `m`.`id`=`mm`.`moduleid` WHERE `m`.`client_id`=0 AND `m`.`module`='mod_fdshop_cart' AND `m`.`title` LIKE 'E2E FDShop Warenkorb%';
DELETE FROM `__PREFIX__modules` WHERE `client_id`=0 AND `module`='mod_fdshop_cart' AND `title` LIKE 'E2E FDShop Warenkorb%';
INSERT INTO `__PREFIX__modules` (`asset_id`,`title`,`note`,`content`,`ordering`,`position`,`checked_out`,`checked_out_time`,`publish_up`,`publish_down`,`published`,`module`,`access`,`showtitle`,`params`,`client_id`,`language`) VALUES
(0,'E2E FDShop Warenkorb 1','','',1,'top-a',NULL,NULL,NULL,NULL,1,'mod_fdshop_cart',1,0,'{"layout":"_:default","moduleclass_sfx":"","cache":"0"}',0,'*'),
(0,'E2E FDShop Warenkorb 2','','',2,'top-a',NULL,NULL,NULL,NULL,1,'mod_fdshop_cart',1,0,'{"layout":"_:default","moduleclass_sfx":"","cache":"0"}',0,'*');
INSERT INTO `__PREFIX__modules_menu` (`moduleid`,`menuid`)
SELECT `id`,0 FROM `__PREFIX__modules`
WHERE `client_id`=0 AND `module`='mod_fdshop_cart' AND `title` LIKE 'E2E FDShop Warenkorb%';

COMMIT;
