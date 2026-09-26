ALTER TABLE `#__fdshop_orders`
  ADD COLUMN `confirmation_pdf_path` VARCHAR(255) NULL AFTER `mail_warning`,
  ADD COLUMN `confirmation_pdf_sha256` CHAR(64) NULL AFTER `confirmation_pdf_path`;

ALTER TABLE `#__fdshop_order_items`
  ADD COLUMN `document_image_path` VARCHAR(500) NULL AFTER `is_removed`,
  ADD COLUMN `packing_group` TINYINT UNSIGNED NULL AFTER `document_image_path`;

ALTER TABLE `#__fdshop_order_bundle_items`
  ADD COLUMN `document_image_path` VARCHAR(500) NULL AFTER `is_removed`,
  ADD COLUMN `packing_group` TINYINT UNSIGNED NULL AFTER `document_image_path`;

ALTER TABLE `#__fdshop_config`
  ADD COLUMN `document_company_name` VARCHAR(255) NOT NULL DEFAULT '',
  ADD COLUMN `document_company_street` VARCHAR(255) NOT NULL DEFAULT '',
  ADD COLUMN `document_company_postal_code` VARCHAR(32) NOT NULL DEFAULT '',
  ADD COLUMN `document_company_city` VARCHAR(120) NOT NULL DEFAULT '',
  ADD COLUMN `document_company_phone` VARCHAR(64) NOT NULL DEFAULT '',
  ADD COLUMN `document_company_email` VARCHAR(255) NOT NULL DEFAULT '',
  ADD COLUMN `document_company_website` VARCHAR(255) NOT NULL DEFAULT '',
  ADD COLUMN `document_company_logo` VARCHAR(500) NOT NULL DEFAULT '',
  ADD COLUMN `document_account_holder` VARCHAR(255) NOT NULL DEFAULT '',
  ADD COLUMN `document_bank_name` VARCHAR(255) NOT NULL DEFAULT '',
  ADD COLUMN `document_iban` VARCHAR(64) NOT NULL DEFAULT '',
  ADD COLUMN `document_bic` VARCHAR(32) NOT NULL DEFAULT '',
  ADD COLUMN `document_footer_text` TEXT NULL,
  ADD COLUMN `document_payment_days` INT UNSIGNED NOT NULL DEFAULT 7,
  ADD COLUMN `document_special_category_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN `document_collection_one_title` VARCHAR(255) NOT NULL DEFAULT 'Sammlung: Batterien, Raketen, Single Shots etc.',
  ADD COLUMN `document_collection_two_title` VARCHAR(255) NOT NULL DEFAULT 'Sammlung: Verbünde';

UPDATE `#__fdshop_config` c
SET c.document_special_category_id = COALESCE((
  SELECT MIN(cat.id) FROM `#__fdshop_categories` cat
  WHERE LOWER(cat.category_name) = LOWER('Verbundfeuerwerk')
), 0)
WHERE c.id = 1 AND c.document_special_category_id = 0;
