-- FDShop update 0.0.10
-- Add order list support fields to orders table

ALTER TABLE `#__fdshop_orders`
  ADD COLUMN `payment_method_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `buyer_group_id`,
  ADD COLUMN `shipment_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `payment_method_id`,
  ADD COLUMN `state` TINYINT NOT NULL DEFAULT 1 AFTER `order_status_id`,
  ADD KEY `idx_fdshop_orders_payment_method_id` (`payment_method_id`),
  ADD KEY `idx_fdshop_orders_shipment_id` (`shipment_id`),
  ADD KEY `idx_fdshop_orders_state` (`state`);