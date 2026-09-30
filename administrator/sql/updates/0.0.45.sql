ALTER TABLE `#__fdshop_config`
  ADD COLUMN `display_timezone` VARCHAR(64) NOT NULL DEFAULT 'Europe/Berlin' AFTER `paypal_reservation_minutes`;
