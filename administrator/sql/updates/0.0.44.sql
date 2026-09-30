ALTER TABLE `#__fdshop_orders`
  ADD COLUMN `invoice_number` VARCHAR(32) NULL AFTER `confirmation_pdf_sha256`,
  ADD COLUMN `invoice_created_at` DATETIME NULL AFTER `invoice_number`,
  ADD COLUMN `invoice_cancelled_at` DATETIME NULL AFTER `invoice_created_at`,
  ADD UNIQUE KEY `uk_fdshop_orders_invoice_number` (`invoice_number`);

CREATE TABLE IF NOT EXISTS `#__fdshop_invoice_sequences` (
  `period` CHAR(4) NOT NULL,
  `last_number` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `#__fdshop_order_documents`
  ADD COLUMN `document_status` VARCHAR(16) NOT NULL DEFAULT 'issued' AFTER `document_type`,
  ADD COLUMN `invoice_number` VARCHAR(32) NULL AFTER `document_status`;
