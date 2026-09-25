ALTER TABLE `#__fdshop_orders`
  ADD COLUMN `customer_first_name` VARCHAR(100) NULL AFTER `customer_email`,
  ADD COLUMN `customer_last_name` VARCHAR(100) NULL AFTER `customer_first_name`,
  ADD COLUMN `customer_company` VARCHAR(255) NULL AFTER `customer_last_name`,
  ADD COLUMN `customer_street` VARCHAR(255) NULL AFTER `customer_company`,
  ADD COLUMN `customer_postal_code` VARCHAR(32) NULL AFTER `customer_street`,
  ADD COLUMN `customer_city` VARCHAR(120) NULL AFTER `customer_postal_code`,
  ADD COLUMN `customer_country` VARCHAR(120) NULL AFTER `customer_city`,
  ADD COLUMN `customer_phone` VARCHAR(64) NULL AFTER `customer_country`;
