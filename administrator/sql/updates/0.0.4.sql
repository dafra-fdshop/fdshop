-- FDShop update 0.0.4
-- Adjust fdshop config table field names and defaults

CREATE TABLE IF NOT EXISTS `#__fdshop_config` (
  `id` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `general_vat_rate` DECIMAL(7,4) NOT NULL DEFAULT 19.0000,
  `image_size_default` INT UNSIGNED NOT NULL DEFAULT 400,
  `image_size_small` INT UNSIGNED NOT NULL DEFAULT 250,
  `image_size_mobile` INT UNSIGNED NOT NULL DEFAULT 100,
  `image_size_manufacturer` INT UNSIGNED NOT NULL DEFAULT 400,

  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__fdshop_config` (
  `id`,
  `general_vat_rate`,
  `image_size_default`,
  `image_size_small`,
  `image_size_mobile`,
  `image_size_manufacturer`
)
SELECT
  1,
  19.0000,
  400,
  250,
  100,
  400
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1
  FROM `#__fdshop_config`
  WHERE `id` = 1
);