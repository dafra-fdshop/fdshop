SET NAMES utf8mb4;
START TRANSACTION;

UPDATE `__PREFIX__fdshop_config` SET `show_terms_checkbox`=1, `require_terms_checkbox`=1 WHERE `id`=1;
UPDATE `__PREFIX__fdshop_products` SET `min_order_qty`=0, `max_order_qty`=0, `step_order_qty`=0 WHERE `id`=900100;
UPDATE `__PREFIX__fdshop_products` SET `min_order_qty`=2, `max_order_qty`=8, `step_order_qty`=2 WHERE `id`=900105;
UPDATE `__PREFIX__fdshop_shipments` SET `published`=1, `is_default`=0 WHERE `id`=900601;
UPDATE `__PREFIX__fdshop_payment_methods` SET `published`=1, `is_default`=0 WHERE `id`=900611;

INSERT INTO `__PREFIX__fdshop_cart` (`id`,`user_id`,`session_id`,`product_id`,`buyer_group_id`,`quantity`,`unit_price_net`,`unit_price_gross`,`currency`,`created`) VALUES
(910000,__JOOMLA_USER_ID__,'',900100,900020,1,16.7983,19.9900,'EUR','2026-01-10 10:00:00'),
(910001,__JOOMLA_USER_ID__,'',900105,900020,2,33.6050,39.9900,'EUR','2026-01-10 10:01:00'),
(910002,__JOOMLA_USER_ID__,'',900107,900020,1,22.6891,27.0000,'EUR','2026-01-10 10:02:00'),
(910003,__JOOMLA_USER_ID__+1000,'',900103,900020,1,19.3277,23.0000,'EUR','2026-01-10 10:03:00');

DELETE FROM `__PREFIX__menu` WHERE `alias`='warenkorb' AND `link` LIKE 'index.php?option=com_fdshop&view=cart%';
INSERT INTO `__PREFIX__menu`
(`id`,`menutype`,`title`,`alias`,`note`,`path`,`link`,`type`,`published`,`parent_id`,`level`,`component_id`,`checked_out`,`checked_out_time`,`browserNav`,`access`,`img`,`template_style_id`,`params`,`lft`,`rgt`,`home`,`language`,`client_id`,`publish_up`,`publish_down`)
VALUES
(900901,'mainmenu','Warenkorb','warenkorb','','warenkorb','index.php?option=com_fdshop&view=cart','component',1,1,1,__COMPONENT_ID__,0,NULL,0,1,'',0,'{}',__MENU_LFT__,__MENU_RGT__,0,'*',0,NULL,NULL);

COMMIT;
