<?php

namespace FDShop\Component\FDShop\Site\Service;

defined('_JEXEC') or die;

interface BundleServiceInterface
{
    public function getBundlesForProduct(int $productId): array;
    public function getBuilder(int $bundleId, int $userId = 0, int $savedBundleId = 0, int $cartBundleId = 0, string $sessionId = ''): array;
    public function calculate(int $bundleId, array $items, int $userId = 0, string $sessionId = '', int $excludeCartBundleId = 0): array;
    public function save(int $userId, int $bundleId, string $name, array $items, int $savedBundleId = 0): int;
    public function deleteSaved(int $userId, int $savedBundleId): void;
    public function addToCart(int $userId, string $sessionId, int $bundleId, array $items, int $cartBundleId = 0): int;
    public function removeFromCart(int $userId, string $sessionId, int $cartBundleId): void;
    public function loadCartBundles(int $userId, string $sessionId): array;
    public function cartDemand(int $userId, string $sessionId, int $productId, int $excludeCartBundleId = 0): float;
}
