ALTER TABLE `#__fdshop_payment_methods`
  ADD COLUMN `provider` VARCHAR(32) NOT NULL DEFAULT '' AFTER `paypal_enabled`,
  ADD KEY `idx_fdshop_payment_methods_provider` (`provider`);

UPDATE `#__fdshop_payment_methods`
SET `provider` = 'paypal'
WHERE `paypal_enabled` = 1 AND `provider` = '';
