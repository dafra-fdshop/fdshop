<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Service;

defined('_JEXEC') or die;

interface BundleServiceInterface
{
    public function saveBundle(
        array $data,
        array $productIds = [],
        array $discountRules = []
    ): int;

    public function saveBundleItems(int $bundleId, array $productIds): void;

    public function saveBundleDiscountRules(int $bundleId, array $discountRules): void;

    public function generateNextBundleNumber(): string;

    public function getBundleById(int $bundleId): ?object;

    public function getBestDiscountRule(int $bundleId, float $quantity): ?object;

    public function findProductBySku(string $sku): ?object;
}
