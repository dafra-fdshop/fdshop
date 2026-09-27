<?php
namespace FDShop\Component\FDShop\Administrator\Service;
defined('_JEXEC') or die;
interface BuyerEligibilityServiceInterface
{
    public function userStatus(int $userId): string;
    public function userHasF3Permission(int $userId): bool;
    public function requiredBuyerStatus(int $productId): string;
    public function canPurchaseProduct(int $userId, int $productId): bool;
    public function assertProductsEligible(int $userId, array $productIds, string $context = 'cart'): void;
    public function setUserStatus(int $userId, string $status): void;
    public function groupId(string $status): int;
}
