<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Service;

defined('_JEXEC') or die;

use DateTimeInterface;

interface CouponServiceInterface
{
    public function saveCoupon(array $data): int;

    public function deleteCoupons(array $couponIds): bool;

    public function getCouponById(int $couponId): ?object;

    public function getCouponByCode(string $couponCode): ?object;

    public function validateCouponData(array $data): void;

    public function prepareRedemptionCheck(
        string $couponCode,
        float $orderTotal = 0.0,
        ?int $userId = null,
        ?DateTimeInterface $date = null
    ): array;

    public function getCouponMappings(int $couponId): array;

    public function saveCouponMappings(int $couponId, array $mappings): void;

    public function deleteCouponMappings(int $couponId): void;

    public function getAssignedUserIds(int $couponId): array;

    public function getAssignedBuyerGroupIds(int $couponId): array;

    public function getAssignedProductIds(int $couponId): array;

    public function getAssignedCategoryIds(int $couponId): array;
}
