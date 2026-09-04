SET NAMES utf8mb4;
START TRANSACTION;

DELETE FROM `__PREFIX__fdshop_coupon_usage`;
DELETE FROM `__PREFIX__fdshop_coupon_user_map`;
DELETE FROM `__PREFIX__fdshop_coupon_buyer_group_map`;
DELETE FROM `__PREFIX__fdshop_coupon_product_map`;
DELETE FROM `__PREFIX__fdshop_coupon_category_map`;
DELETE FROM `__PREFIX__fdshop_coupons`;
DELETE FROM `__PREFIX__fdshop_order_status_history`;
DELETE FROM `__PREFIX__fdshop_order_history`;
DELETE FROM `__PREFIX__fdshop_order_bundle_items`;
DELETE FROM `__PREFIX__fdshop_order_bundles`;
DELETE FROM `__PREFIX__fdshop_order_items`;
DELETE FROM `__PREFIX__fdshop_orders`;
DELETE FROM `__PREFIX__fdshop_order_statuses`;
DELETE FROM `__PREFIX__fdshop_cart`;
DELETE FROM `__PREFIX__fdshop_shipments`;
DELETE FROM `__PREFIX__fdshop_payment_methods`;
DELETE FROM `__PREFIX__fdshop_bundle_discount_rules`;
DELETE FROM `__PREFIX__fdshop_bundle_items`;
DELETE FROM `__PREFIX__fdshop_bundles`;
DELETE FROM `__PREFIX__fdshop_media`;
DELETE FROM `__PREFIX__fdshop_product_buyer_group_map`;
DELETE FROM `__PREFIX__fdshop_user_buyer_group_map`;
DELETE FROM `__PREFIX__fdshop_product_category_map`;
DELETE FROM `__PREFIX__fdshop_product_prices_research`;
DELETE FROM `__PREFIX__fdshop_product_prices`;
DELETE FROM `__PREFIX__fdshop_price_calc_rules`;
DELETE FROM `__PREFIX__fdshop_products_details`;
DELETE FROM `__PREFIX__fdshop_products`;
DELETE FROM `__PREFIX__fdshop_categories`;
DELETE FROM `__PREFIX__fdshop_manufacturers`;
DELETE FROM `__PREFIX__fdshop_buyer_groups`;

UPDATE `__PREFIX__fdshop_config` SET general_vat_rate=19.0000,general_currency='EUR',image_size_default=400,image_size_small=250,image_size_mobile=100,image_size_manufacturer=400,show_terms_checkbox=0,require_terms_checkbox=0,katalog_active=0 WHERE id=1;

INSERT INTO `__PREFIX__fdshop_manufacturers`
(id,manufacturer_name,alias,description,meta_title,is_active,ordering,created,created_by)
VALUES
(900001,'E2E Hersteller Aktiv','e2e-manufacturer-active','Künstlicher Testhersteller','E2E Hersteller Aktiv',1,10,'2026-01-01 00:00:00',0),
(900002,'E2E Hersteller Inaktiv','e2e-manufacturer-inactive','Künstlicher Testhersteller','E2E Hersteller Inaktiv',0,20,'2026-01-01 00:00:00',0);

INSERT INTO `__PREFIX__fdshop_categories`
(id,parent_id,category_name,alias,path,description,level,ordering,is_active,created,created_by)
VALUES
(900010,0,'E2E Hauptkategorie','e2e-main','e2e-main','Künstliche Hauptkategorie',1,10,1,'2026-01-01 00:00:00',0),
(900011,900010,'E2E Unterkategorie','e2e-child','e2e-main/e2e-child','Künstliche Unterkategorie',2,11,1,'2026-01-01 00:00:00',0),
(900012,0,'E2E Zweite Kategorie','e2e-second','e2e-second','Künstliche unabhängige Kategorie',1,20,1,'2026-01-01 00:00:00',0),
(900013,0,'E2E Inaktive Kategorie','e2e-inactive','e2e-inactive','Künstliche inaktive Kategorie',1,30,0,'2026-01-01 00:00:00',0);

INSERT INTO `__PREFIX__fdshop_buyer_groups`
(id,group_name,alias,is_active,ordering,created,created_by)
VALUES
(900020,'E2E Standard','e2e-standard',1,10,'2026-01-01 00:00:00',0),
(900021,'E2E Stammkunde','e2e-regular',1,20,'2026-01-01 00:00:00',0),
(900022,'E2E Händler','e2e-dealer',1,30,'2026-01-01 00:00:00',0);

INSERT INTO `__PREFIX__fdshop_products`
(id,manufacturer_id,product_name,alias,short_description,description,buyer_group_id,sale_price,discount_price,discount_active,currency,min_order_qty,max_order_qty,step_order_qty,is_active,is_deleted,meta_title,in_stock,unit_type,unit_quantity)
VALUES
(900100,900001,'E2E Produkt Aktiv','e2e-prod-active','Künstlich','Aktiv mit Bestand',900020,19.9900,0,0,'EUR',1,10,1,1,0,'E2E Aktiv','available','Stück',1),
(900101,900002,'E2E Produkt Inaktiv','e2e-prod-inactive','Künstlich','Inaktiv',900020,21.0000,0,0,'EUR',1,10,1,0,0,'E2E Inaktiv','available','Stück',1),
(900102,900001,'E2E Produkt Papierkorb','e2e-prod-deleted','Künstlich','Papierkorb',900020,22.0000,0,0,'EUR',1,10,1,0,1,'E2E Papierkorb','available','Stück',1),
(900103,900001,'E2E Produkt Bild','e2e-prod-image','Künstlich','Mit synthetischem Bild',900020,23.0000,0,0,'EUR',1,10,1,1,0,'E2E Bild','available','Stück',1),
(900104,900001,'E2E Produkt Ohne Bild','e2e-prod-noimage','Künstlich','Ohne Bild',900020,24.0000,0,0,'EUR',1,10,1,1,0,'E2E Ohne Bild','available','Stück',1),
(900105,900001,'E2E Produkt Aktionspreis','e2e-prod-discount','Künstlich','Aktiver Aktionspreis',900020,50.0000,39.9900,1,'EUR',1,10,1,1,0,'E2E Aktionspreis','available','Stück',1),
(900106,900001,'E2E Produkt Ausverkauft','e2e-prod-soldout','Künstlich','Ausverkauft',900020,26.0000,0,0,'EUR',1,10,1,1,0,'E2E Ausverkauft','sold_out','Stück',1),
(900107,900001,'E2E Produkt Niedrigbestand','e2e-prod-low','Künstlich','Niedriger Bestand',900020,27.0000,0,0,'EUR',1,10,1,1,0,'E2E Niedrigbestand','low_stock','Stück',1),
(900108,900001,'E2E Produkt Mehrfachkategorie','e2e-prod-multicat','Künstlich','Mehrere Kategorien',900020,28.0000,0,0,'EUR',1,10,1,1,0,'E2E Mehrfachkategorie','available','Stück',1),
(900109,900001,'E2E Produkt Käufergruppen','e2e-prod-buyergroup','Künstlich','Mehrere Käufergruppen',900021,29.0000,0,0,'EUR',1,10,1,1,0,'E2E Käufergruppen','available','Stück',1);

INSERT INTO `__PREFIX__fdshop_products_details`
(id,product_id,sku,gtin,stock_quantity,low_stock,reserved_quantity,sold_quantity,is_in_stock,created,created_by,weight,length,width,height)
VALUES
(900200,900100,'E2E-PROD-ACTIVE','9900000000001',20,5,0,0,1,'2026-01-01 00:00:00',0,1,10,10,10),
(900201,900101,'E2E-PROD-INACTIVE','9900000000002',20,5,0,0,1,'2026-01-01 00:00:00',0,1,10,10,10),
(900202,900102,'E2E-PROD-DELETED','9900000000003',20,5,0,0,1,'2026-01-01 00:00:00',0,1,10,10,10),
(900203,900103,'E2E-PROD-IMAGE','9900000000004',20,5,0,0,1,'2026-01-01 00:00:00',0,1,10,10,10),
(900204,900104,'E2E-PROD-NOIMAGE','9900000000005',20,5,0,0,1,'2026-01-01 00:00:00',0,1,10,10,10),
(900205,900105,'E2E-PROD-DISCOUNT','9900000000006',20,5,0,0,1,'2026-01-01 00:00:00',0,1,10,10,10),
(900206,900106,'E2E-PROD-SOLDOUT','9900000000007',0,5,0,0,0,'2026-01-01 00:00:00',0,1,10,10,10),
(900207,900107,'E2E-PROD-LOW','9900000000008',2,5,0,0,1,'2026-01-01 00:00:00',0,1,10,10,10),
(900208,900108,'E2E-PROD-MULTICAT','9900000000009',20,5,0,0,1,'2026-01-01 00:00:00',0,1,10,10,10),
(900209,900109,'E2E-PROD-BUYERGROUP','9900000000010',20,5,0,0,1,'2026-01-01 00:00:00',0,1,10,10,10);

INSERT INTO `__PREFIX__fdshop_product_category_map` (id,product_id,category_id,is_primary) VALUES
(900300,900100,900010,1),(900301,900101,900010,1),(900302,900102,900010,1),(900303,900103,900011,1),(900304,900104,900011,1),(900305,900105,900012,1),(900306,900106,900012,1),(900307,900107,900012,1),(900308,900108,900010,1),(900309,900108,900012,0),(900310,900109,900011,1);
INSERT INTO `__PREFIX__fdshop_product_buyer_group_map` (id,product_id,buyer_group_id) VALUES
(900320,900109,900020),(900321,900109,900021),(900322,900109,900022);
INSERT INTO `__PREFIX__fdshop_user_buyer_group_map` (id,user_id,buyer_group_id) VALUES (900330,__JOOMLA_USER_ID__,900021);
INSERT INTO `__PREFIX__fdshop_media` (id,product_id,media_type,file_name,file_type,path_standard,path_small,path_mobile,path_invoice,is_primary,ordering,created,created_by) VALUES
(900340,900103,'image','e2e-fixture-product.svg','image/svg+xml','images/FDShop/products/e2e-fixture-product.svg','images/FDShop/products/e2e-fixture-product.svg','images/FDShop/products/e2e-fixture-product.svg','images/FDShop/products/e2e-fixture-product.svg',1,1,'2026-01-01 00:00:00',0);

INSERT INTO `__PREFIX__fdshop_bundles` (id,bundle_number,bundle_name,alias,description,is_active,created,created_by) VALUES
(900400,'E2E-BUNDLE-ACTIVE','E2E Bundle Aktiv','e2e-bundle-active','Künstliches aktives Bundle',1,'2026-01-01 00:00:00',0),(900401,'E2E-BUNDLE-INACTIVE','E2E Bundle Inaktiv','e2e-bundle-inactive','Künstliches inaktives Bundle',0,'2026-01-01 00:00:00',0);
INSERT INTO `__PREFIX__fdshop_bundle_items` (id,bundle_id,product_id,ordering,created,created_by) VALUES
(900410,900400,900100,1,'2026-01-01 00:00:00',0),(900411,900400,900105,2,'2026-01-01 00:00:00',0);
INSERT INTO `__PREFIX__fdshop_bundle_discount_rules` (id,bundle_id,min_quantity,discount_percent,ordering,created,created_by) VALUES
(900420,900400,2,5,1,'2026-01-01 00:00:00',0),(900421,900400,4,10,2,'2026-01-01 00:00:00',0);

INSERT INTO `__PREFIX__fdshop_coupons` (id,coupon_code,coupon_name,alias,description,discount_type,discount_value,minimum_order_total,usage_limit_total,usage_limit_per_user,valid_from,valid_to,published,ordering,created,created_by) VALUES
(900500,'E2E-PERCENT','E2E Prozent gültig','e2e-percent','Technischer Prozentfall','percent',10,0,0,0,'2025-01-01 00:00:00','2035-12-31 23:59:59',1,10,'2026-01-01 00:00:00',0),
(900501,'E2E-FIXED','E2E Fixbetrag gültig','e2e-fixed','Technischer Fixbetragfall','fixed',5,0,0,0,'2025-01-01 00:00:00','2035-12-31 23:59:59',1,20,'2026-01-01 00:00:00',0),
(900502,'E2E-EXPIRED','E2E Abgelaufen','e2e-expired','Abgelaufener Fall','percent',10,0,0,0,'2020-01-01 00:00:00','2020-12-31 23:59:59',1,30,'2026-01-01 00:00:00',0),
(900503,'E2E-FUTURE','E2E Zukünftig','e2e-future','Noch nicht gültig','percent',10,0,0,0,'2035-01-01 00:00:00','2035-12-31 23:59:59',1,40,'2026-01-01 00:00:00',0),
(900504,'E2E-MINIMUM','E2E Mindestbestellwert','e2e-minimum','Mit Mindestbestellwert','fixed',8,100,0,0,'2025-01-01 00:00:00','2035-12-31 23:59:59',1,50,'2026-01-01 00:00:00',0),
(900505,'E2E-PRODUCT','E2E Produktbeschränkt','e2e-product','Produktmapping','percent',7,0,0,0,'2025-01-01 00:00:00','2035-12-31 23:59:59',1,60,'2026-01-01 00:00:00',0),
(900506,'E2E-CATEGORY','E2E Kategoriebeschränkt','e2e-category','Kategoriemapping','percent',6,0,0,0,'2025-01-01 00:00:00','2035-12-31 23:59:59',1,70,'2026-01-01 00:00:00',0),
(900507,'E2E-GROUP-USER','E2E Gruppen Benutzer','e2e-group-user','Gruppen- und Benutzermapping','fixed',4,0,10,2,'2025-01-01 00:00:00','2035-12-31 23:59:59',1,80,'2026-01-01 00:00:00',0);
INSERT INTO `__PREFIX__fdshop_coupon_product_map` (id,coupon_id,product_id,created,created_by) VALUES (900510,900505,900100,'2026-01-01 00:00:00',0);
INSERT INTO `__PREFIX__fdshop_coupon_category_map` (id,coupon_id,category_id,created,created_by) VALUES (900511,900506,900010,'2026-01-01 00:00:00',0);
INSERT INTO `__PREFIX__fdshop_coupon_buyer_group_map` (id,coupon_id,buyer_group_id,created,created_by) VALUES (900512,900507,900021,'2026-01-01 00:00:00',0);
INSERT INTO `__PREFIX__fdshop_coupon_user_map` (id,coupon_id,user_id,created,created_by) VALUES (900513,900507,__JOOMLA_USER_ID__,'2026-01-01 00:00:00',0);

INSERT INTO `__PREFIX__fdshop_shipments` (id,shipment_name,shipment_description,shipment_color,shipment_price,published,is_default,ordering,created,created_by) VALUES
(900600,'E2E Versand Standard','Eindeutig künstlich','#112233',4.99,1,1,10,'2026-01-01 00:00:00',0),(900601,'E2E Versand Inaktiv','Eindeutig künstlich','#445566',9.99,0,0,20,'2026-01-01 00:00:00',0);
INSERT INTO `__PREFIX__fdshop_payment_methods` (id,payment_name,payment_description,payment_fee,paypal_enabled,published,is_default,ordering,created,created_by) VALUES
(900610,'E2E Zahlung Rechnung','Eindeutig künstlich',0,0,1,1,10,'2026-01-01 00:00:00',0),(900611,'E2E Zahlung Inaktiv','Eindeutig künstlich',2.5,0,0,0,20,'2026-01-01 00:00:00',0);
INSERT INTO `__PREFIX__fdshop_order_statuses` (id,status_code,status_name,description,notify_seller,notify_buyer,create_invoice,stock_action,seller_email_mode,buyer_email_mode,is_active,ordering,created,created_by) VALUES
(900700,'e2e-ordered','E2E Bestellt','Künstlich',1,1,0,'reserve','config','account',1,10,'2026-01-01 00:00:00',0),(900701,'e2e-shipped','E2E Versendet','Künstlich',0,1,0,'deduct','config','account',1,20,'2026-01-01 00:00:00',0),(900702,'e2e-inactive','E2E Status Inaktiv','Künstlich',0,0,0,'none','config','account',0,30,'2026-01-01 00:00:00',0);

INSERT INTO `__PREFIX__fdshop_orders` (id,order_number,user_id,buyer_group_id,payment_method_id,shipment_id,order_status,order_status_id,state,currency,grand_total,has_bundle,created) VALUES
(900800,'E2E-ORDER-NORMAL',__JOOMLA_USER_ID__,900020,900610,900600,'e2e-ordered',900700,1,'EUR',19.99,0,'2026-01-02 10:00:00'),(900801,'E2E-ORDER-BUNDLE',__JOOMLA_USER_ID__,900021,900610,900600,'e2e-shipped',900701,1,'EUR',66.48,1,'2026-01-03 10:00:00');
INSERT INTO `__PREFIX__fdshop_order_items` (id,order_id,product_id,product_name,sku,gtin,manufacturer_name,quantity,regular_price_gross,discount_price_gross,unit_price_net,unit_price_gross,tax_rate,line_total_net,line_total_gross,currency,is_removed) VALUES
(900810,900800,900100,'E2E Produkt Aktiv','E2E-PROD-ACTIVE','9900000000001','E2E Hersteller Aktiv',1,19.99,0,16.7983,19.99,19,16.7983,19.99,'EUR',0),(900811,900801,900105,'E2E Produkt Aktionspreis','E2E-PROD-DISCOUNT','9900000000006','E2E Hersteller Aktiv',1,50,39.99,33.6050,39.99,19,33.6050,39.99,'EUR',0);
INSERT INTO `__PREFIX__fdshop_order_bundles` (id,order_id,bundle_id,bundle_number,bundle_name,quantity_items,subtotal_net,subtotal_gross,discount_percent,discount_amount_net,discount_amount_gross,total_net,total_gross,is_removed,created) VALUES
(900820,900801,900400,'E2E-BUNDLE-ACTIVE','E2E Bundle Aktiv',2,58.4033,69.50,5,2.9202,3.4750,55.4831,66.0250,0,'2026-01-03 10:00:00');
INSERT INTO `__PREFIX__fdshop_order_bundle_items` (id,order_bundle_id,product_id,product_name,sku,gtin,manufacturer_name,quantity,regular_price_gross,discount_price_gross,unit_price_net,unit_price_gross,tax_rate,total_net,total_gross,currency,is_removed,created) VALUES
(900830,900820,900100,'E2E Produkt Aktiv','E2E-PROD-ACTIVE','9900000000001','E2E Hersteller Aktiv',1,19.99,0,16.7983,19.99,19,16.7983,19.99,'EUR',0,'2026-01-03 10:00:00'),(900831,900820,900105,'E2E Produkt Aktionspreis','E2E-PROD-DISCOUNT','9900000000006','E2E Hersteller Aktiv',1,50,39.99,33.6050,39.99,19,33.6050,39.99,'EUR',0,'2026-01-03 10:00:00');
INSERT INTO `__PREFIX__fdshop_order_history` (id,order_id,event_type,event_title,event_text,reference_type,reference_id,is_system_event,created,created_by) VALUES
(900840,900800,'created','E2E Snapshot angelegt','Kein Checkout-Nachweis','order',900800,1,'2026-01-02 10:00:00',0),(900841,900801,'created','E2E Bundle-Snapshot angelegt','Kein Checkout-Nachweis','order',900801,1,'2026-01-03 10:00:00',0);
INSERT INTO `__PREFIX__fdshop_order_status_history` (id,order_id,old_status_id,new_status_id,comment,is_system_change,changed_at,changed_by) VALUES
(900850,900800,NULL,900700,'Künstlicher Ausgangsstatus',1,'2026-01-02 10:00:00',0),(900851,900801,900700,900701,'Künstlicher Statuswechsel',1,'2026-01-03 10:00:00',0);
COMMIT;
