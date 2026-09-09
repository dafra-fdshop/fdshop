SET NAMES utf8mb4;
START TRANSACTION;

UPDATE `__PREFIX__fdshop_products`
SET `in_stock` = CASE `id`
    WHEN 900100 THEN 'Verfügbar'
    WHEN 900103 THEN 'Bestellbar'
    WHEN 900104 THEN 'wenige Bestellbar'
    WHEN 900105 THEN 'Verfügbar'
    WHEN 900106 THEN 'Ausverkauft'
    WHEN 900107 THEN 'wenige Verfügbar'
    WHEN 900108 THEN 'Verfügbar'
    ELSE `in_stock`
END
WHERE `id` IN (900100,900103,900104,900105,900106,900107,900108);

UPDATE `__PREFIX__fdshop_products`
SET `nem` = 125.500, `shot_count` = 25.000, `caliber` = '20 mm', `burn_time` = '35 s', `rise_height` = '30 m',
    `ribbon_new` = 1, `ribbon_hot` = 1, `ribbon_bundle` = 1
WHERE `id` = 900100;

INSERT IGNORE INTO `__PREFIX__fdshop_product_category_map` (`id`,`product_id`,`category_id`,`is_primary`) VALUES
(903100,900103,900010,0),(903101,900104,900010,0),(903102,900105,900010,0),(903103,900106,900010,0),(903104,900107,900010,0);

INSERT INTO `__PREFIX__fdshop_media`
(`id`,`product_id`,`media_type`,`external_url`,`file_name`,`file_type`,`path_standard`,`path_small`,`path_mobile`,`path_invoice`,`is_primary`,`ordering`,`created`,`created_by`)
VALUES (900341,900100,'youtube','https://www.youtube.com/embed/aqz-KE-bpKQ',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-01-01 00:00:00',0);

INSERT INTO `__PREFIX__fdshop_media`
(`id`,`product_id`,`media_type`,`external_url`,`file_name`,`file_type`,`path_standard`,`path_small`,`path_mobile`,`path_invoice`,`is_primary`,`ordering`,`created`,`created_by`)
VALUES
(900342,900100,'image',NULL,'e2e-fixture-product.svg','image/svg+xml','images/FDShop/products/e2e-fixture-product.svg',NULL,NULL,NULL,1,1,'2026-01-01 00:00:00',0),
(900343,900100,'image',NULL,'product-placeholder.svg','image/svg+xml','media/com_fdshop/images/product-placeholder.svg',NULL,NULL,NULL,0,2,'2026-01-01 00:00:00',0);

INSERT INTO `__PREFIX__fdshop_products`
(`id`,`manufacturer_id`,`product_name`,`alias`,`short_description`,`description`,`buyer_group_id`,`sale_price`,`discount_price`,`discount_active`,`currency`,`min_order_qty`,`max_order_qty`,`step_order_qty`,`is_active`,`is_deleted`,`publish_up`,`publish_down`,`meta_title`,`in_stock`,`unit_type`,`unit_quantity`)
SELECT 901000+n,900001,CONCAT('E2E Seitenprodukt ',LPAD(n,2,'0')),CONCAT('e2e-page-',LPAD(n,2,'0')),'Künstliches Pagination-Produkt','Nur für den isolierten Frontendtest',900020,
       100+n,CASE WHEN n=5 THEN 50 ELSE 0 END,CASE WHEN n=5 THEN 1 ELSE 0 END,'EUR',1,10,1,1,0,
       CASE WHEN n=23 THEN '2035-01-01 00:00:00' ELSE NULL END,
       CASE WHEN n=24 THEN '2020-01-01 00:00:00' ELSE NULL END,
       CONCAT('E2E Seitenprodukt ',n),CASE WHEN MOD(n,2)=0 THEN 'Verfügbar' ELSE 'Bestellbar' END,'Stück',1
FROM (WITH RECURSIVE sequence AS (SELECT 1 n UNION ALL SELECT n+1 FROM sequence WHERE n<25) SELECT n FROM sequence) numbers;

INSERT INTO `__PREFIX__fdshop_products_details`
(`id`,`product_id`,`sku`,`gtin`,`stock_quantity`,`low_stock`,`reserved_quantity`,`sold_quantity`,`is_in_stock`,`created`,`created_by`,`weight`,`length`,`width`,`height`)
SELECT 902000+n,901000+n,CONCAT('E2E-PAGE-',LPAD(n,2,'0')),CONCAT('9910000000',LPAD(n,3,'0')),20,5,0,0,IF(MOD(n,2)=0,1,0),'2026-01-01 00:00:00',0,1,10,10,10
FROM (WITH RECURSIVE sequence AS (SELECT 1 n UNION ALL SELECT n+1 FROM sequence WHERE n<25) SELECT n FROM sequence) numbers;

INSERT INTO `__PREFIX__fdshop_product_category_map` (`id`,`product_id`,`category_id`,`is_primary`)
SELECT 904000+n,901000+n,900010,1
FROM (WITH RECURSIVE sequence AS (SELECT 1 n UNION ALL SELECT n+1 FROM sequence WHERE n<25) SELECT n FROM sequence) numbers;

DELETE FROM `__PREFIX__menu` WHERE `alias` = 'batterien' AND `link` LIKE 'index.php?option=com_fdshop&view=category%';
INSERT INTO `__PREFIX__menu`
(`id`,`menutype`,`title`,`alias`,`note`,`path`,`link`,`type`,`published`,`parent_id`,`level`,`component_id`,`checked_out`,`checked_out_time`,`browserNav`,`access`,`img`,`template_style_id`,`params`,`lft`,`rgt`,`home`,`language`,`client_id`,`publish_up`,`publish_down`)
VALUES
(900900,'mainmenu','Batterien','batterien','','batterien','index.php?option=com_fdshop&view=category&id=900010','component',1,1,1,__COMPONENT_ID__,0,NULL,0,1,'',0,'{}',__MENU_LFT__,__MENU_RGT__,0,'*',0,NULL,NULL);

COMMIT;
