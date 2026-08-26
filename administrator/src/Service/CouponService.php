<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Service;

defined('_JEXEC') or die;

use DateTimeInterface;
use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use RuntimeException;

class CouponService implements CouponServiceInterface
{
    private const DISCOUNT_TYPE_PERCENT = 'percent';
    private const DISCOUNT_TYPE_FIXED = 'fixed';

    private const COUPON_MAPPING_TABLES = [
        'user_ids' => [
            'table' => '#__fdshop_coupon_user_map',
            'targetColumn' => 'user_id',
        ],
        'buyer_group_ids' => [
            'table' => '#__fdshop_coupon_buyer_group_map',
            'targetColumn' => 'buyer_group_id',
        ],
        'product_ids' => [
            'table' => '#__fdshop_coupon_product_map',
            'targetColumn' => 'product_id',
        ],
        'category_ids' => [
            'table' => '#__fdshop_coupon_category_map',
            'targetColumn' => 'category_id',
        ],
    ];

    public function __construct(
        private readonly MVCFactoryInterface $mvcFactory,
        private readonly DatabaseInterface $db
    ) {
    }

    public function saveCoupon(array $data): int
    {
        $this->validateCouponData($data);

        $mappings = $this->extractCouponMappings($data);

        $table = $this->mvcFactory->createTable('Coupon', 'Administrator');

        if (!$table) {
            throw new RuntimeException('CouponTable konnte nicht erstellt werden.');
        }

        $bindData = $this->filterCouponData($data);
        $bindData['coupon_code'] = $this->normalizeCouponCode((string) ($bindData['coupon_code'] ?? ''));
        $bindData['coupon_name'] = trim((string) ($bindData['coupon_name'] ?? ''));
        $bindData['discount_type'] = $this->normalizeDiscountType((string) ($bindData['discount_type'] ?? self::DISCOUNT_TYPE_PERCENT));

        $this->db->transactionStart();

        try {
            if (!$table->bind($bindData)) {
                throw new RuntimeException($table->getError());
            }

            if (!$table->check()) {
                throw new RuntimeException($table->getError());
            }

            if (!$table->store()) {
                throw new RuntimeException($table->getError());
            }

            $couponId = (int) $table->id;

            $this->saveCouponMappings($couponId, $mappings);

            $this->db->transactionCommit();

            return $couponId;
        } catch (\Throwable $e) {
            $this->db->transactionRollback();
            throw $e;
        }
    }

    public function deleteCoupons(array $couponIds): bool
    {
        $couponIds = $this->normalizeIds($couponIds);

        if ($couponIds === []) {
            return true;
        }

        $table = $this->mvcFactory->createTable('Coupon', 'Administrator');

        if (!$table) {
            throw new RuntimeException('CouponTable konnte nicht erstellt werden.');
        }

        $this->db->transactionStart();

        try {
            foreach ($couponIds as $couponId) {
                $this->deleteCouponMappings($couponId);

                if (!$table->delete($couponId)) {
                    throw new RuntimeException($table->getError());
                }
            }

            $this->db->transactionCommit();

            return true;
        } catch (\Throwable $e) {
            $this->db->transactionRollback();
            throw $e;
        }
    }

    public function getCouponById(int $couponId): ?object
    {
        if ($couponId <= 0) {
            return null;
        }

        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__fdshop_coupons'))
            ->where($this->db->quoteName('id') . ' = ' . (int) $couponId);

        $this->db->setQuery($query);
        $coupon = $this->db->loadObject();

        if (!$coupon) {
            return null;
        }

        return $this->attachAssignments($coupon);
    }

    public function getCouponByCode(string $couponCode): ?object
    {
        $couponCode = $this->normalizeCouponCode($couponCode);

        if ($couponCode === '') {
            return null;
        }

        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__fdshop_coupons'))
            ->where($this->db->quoteName('coupon_code') . ' = ' . $this->db->quote($couponCode));

        $this->db->setQuery($query);
        $coupon = $this->db->loadObject();

        if (!$coupon) {
            return null;
        }

        return $this->attachAssignments($coupon);
    }

    public function validateCouponData(array $data): void
    {
        $couponCode = $this->normalizeCouponCode((string) ($data['coupon_code'] ?? ''));
        $couponName = trim((string) ($data['coupon_name'] ?? ''));
        $discountType = $this->normalizeDiscountType((string) ($data['discount_type'] ?? self::DISCOUNT_TYPE_PERCENT));
        $discountValue = (float) ($data['discount_value'] ?? 0);
        $minimumOrderTotal = (float) ($data['minimum_order_total'] ?? 0);
        $usageLimitTotal = (int) ($data['usage_limit_total'] ?? 0);
        $usageLimitPerUser = (int) ($data['usage_limit_per_user'] ?? 0);
        $validFrom = trim((string) ($data['valid_from'] ?? ''));
        $validTo = trim((string) ($data['valid_to'] ?? ''));

        if ($couponCode === '') {
            throw new InvalidArgumentException('coupon_code darf nicht leer sein.');
        }

        if ($couponName === '') {
            throw new InvalidArgumentException('coupon_name darf nicht leer sein.');
        }

        if (!in_array($discountType, [self::DISCOUNT_TYPE_PERCENT, self::DISCOUNT_TYPE_FIXED], true)) {
            throw new InvalidArgumentException('discount_type ist ungültig.');
        }

        if ($discountValue <= 0) {
            throw new InvalidArgumentException('discount_value muss größer als 0 sein.');
        }

        if ($discountType === self::DISCOUNT_TYPE_PERCENT && $discountValue > 100) {
            throw new InvalidArgumentException('discount_value darf bei percent nicht größer als 100 sein.');
        }

        if ($minimumOrderTotal < 0) {
            throw new InvalidArgumentException('minimum_order_total darf nicht negativ sein.');
        }

        if ($usageLimitTotal < 0) {
            throw new InvalidArgumentException('usage_limit_total darf nicht negativ sein.');
        }

        if ($usageLimitPerUser < 0) {
            throw new InvalidArgumentException('usage_limit_per_user darf nicht negativ sein.');
        }

        if ($validFrom !== '' && $validTo !== '' && strtotime($validFrom) > strtotime($validTo)) {
            throw new InvalidArgumentException('valid_from darf nicht nach valid_to liegen.');
        }
    }

    public function prepareRedemptionCheck(
        string $couponCode,
        float $orderTotal = 0.0,
        ?int $userId = null,
        ?DateTimeInterface $date = null
    ): array {
        $coupon = $this->getCouponByCode($couponCode);
        $errors = [];

        if (!$coupon) {
            return [
                'valid' => false,
                'coupon' => null,
                'errors' => ['coupon_not_found'],
            ];
        }

        if ((int) $coupon->published !== 1) {
            $errors[] = 'coupon_unpublished';
        }

        $timestamp = $date ? $date->getTimestamp() : Factory::getDate()->toUnix();

        if (!empty($coupon->valid_from) && strtotime((string) $coupon->valid_from) > $timestamp) {
            $errors[] = 'coupon_not_started';
        }

        if (!empty($coupon->valid_to) && strtotime((string) $coupon->valid_to) < $timestamp) {
            $errors[] = 'coupon_expired';
        }

        if ($orderTotal < (float) $coupon->minimum_order_total) {
            $errors[] = 'minimum_order_total_not_reached';
        }

        if ((int) $coupon->usage_limit_total > 0 && $this->getTotalUsageCount((int) $coupon->id) >= (int) $coupon->usage_limit_total) {
            $errors[] = 'usage_limit_total_reached';
        }

        if ($userId !== null && $userId > 0 && (int) $coupon->usage_limit_per_user > 0
            && $this->getUserUsageCount((int) $coupon->id, $userId) >= (int) $coupon->usage_limit_per_user
        ) {
            $errors[] = 'usage_limit_per_user_reached';
        }

        return [
            'valid' => $errors === [],
            'coupon' => $coupon,
            'errors' => $errors,
        ];
    }

    public function getAssignedUserIds(int $couponId): array
    {
        return $this->getCouponMappings($couponId)['user_ids'];
    }

    public function getAssignedBuyerGroupIds(int $couponId): array
    {
        return $this->getCouponMappings($couponId)['buyer_group_ids'];
    }

    public function getAssignedProductIds(int $couponId): array
    {
        return $this->getCouponMappings($couponId)['product_ids'];
    }

    public function getAssignedCategoryIds(int $couponId): array
    {
        return $this->getCouponMappings($couponId)['category_ids'];
    }

    private function attachAssignments(object $coupon): object
    {
        $couponId = (int) $coupon->id;

        foreach ($this->getCouponMappings($couponId) as $key => $ids) {
            $coupon->{$key} = $ids;
        }

        return $coupon;
    }

    private function filterCouponData(array $data): array
    {
        $allowedFields = [
            'id',
            'coupon_code',
            'coupon_name',
            'alias',
            'description',
            'discount_type',
            'discount_value',
            'minimum_order_total',
            'usage_limit_total',
            'usage_limit_per_user',
            'valid_from',
            'valid_to',
            'published',
            'ordering',
        ];

        $filtered = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $filtered[$field] = $data[$field];
            }
        }

        return $filtered;
    }

    public function getCouponMappings(int $couponId): array
    {
        $mappings = [];

        foreach (self::COUPON_MAPPING_TABLES as $key => $mapping) {
            $mappings[$key] = $this->getAssignedIds(
                $couponId,
                $mapping['table'],
                $mapping['targetColumn']
            );
        }

        return $mappings;
    }

    public function saveCouponMappings(int $couponId, array $mappings): void
    {
        if ($couponId <= 0) {
            throw new InvalidArgumentException('couponId ist ungültig.');
        }

        foreach (self::COUPON_MAPPING_TABLES as $key => $mapping) {
            $this->replaceCouponMap(
                $couponId,
                $mapping['table'],
                $mapping['targetColumn'],
                $mappings[$key] ?? []
            );
        }
    }

    public function deleteCouponMappings(int $couponId): void
    {
        if ($couponId <= 0) {
            return;
        }

        foreach (self::COUPON_MAPPING_TABLES as $mapping) {
            $deleteQuery = $this->db->getQuery(true)
                ->delete($this->db->quoteName($mapping['table']))
                ->where($this->db->quoteName('coupon_id') . ' = ' . (int) $couponId);

            $this->db->setQuery($deleteQuery)->execute();
        }
    }

    private function replaceCouponMap(int $couponId, string $tableName, string $targetColumn, array $ids): void
    {
        $this->deleteSingleCouponMap($couponId, $tableName);

        $ids = $this->normalizeIds($ids);

        if ($ids === []) {
            return;
        }

        $created = Factory::getDate()->toSql();
        $createdBy = (int) Factory::getApplication()->getIdentity()->id;
        $columns = [
            $this->db->quoteName('coupon_id'),
            $this->db->quoteName($targetColumn),
            $this->db->quoteName('created'),
            $this->db->quoteName('created_by'),
        ];
        $values = [];

        foreach ($ids as $id) {
            $values[] = implode(', ', [
                (int) $couponId,
                (int) $id,
                $this->db->quote($created),
                (int) $createdBy,
            ]);
        }

        $insertQuery = $this->db->getQuery(true)
            ->insert($this->db->quoteName($tableName))
            ->columns($columns)
            ->values($values);

        $this->db->setQuery($insertQuery)->execute();
    }

    private function deleteSingleCouponMap(int $couponId, string $tableName): void
    {
        if ($couponId <= 0) {
            throw new InvalidArgumentException('couponId ist ungültig.');
        }

        $deleteQuery = $this->db->getQuery(true)
            ->delete($this->db->quoteName($tableName))
            ->where($this->db->quoteName('coupon_id') . ' = ' . (int) $couponId);

        $this->db->setQuery($deleteQuery)->execute();
    }

    private function getAssignedIds(int $couponId, string $tableName, string $targetColumn): array
    {
        if ($couponId <= 0) {
            return [];
        }

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName($targetColumn))
            ->from($this->db->quoteName($tableName))
            ->where($this->db->quoteName('coupon_id') . ' = ' . (int) $couponId)
            ->order($this->db->quoteName('id') . ' ASC');

        $this->db->setQuery($query);

        return array_map('intval', (array) $this->db->loadColumn());
    }

    private function getTotalUsageCount(int $couponId): int
    {
        if ($couponId <= 0) {
            return 0;
        }

        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__fdshop_coupon_usage'))
            ->where($this->db->quoteName('coupon_id') . ' = ' . (int) $couponId);

        $this->db->setQuery($query);

        return (int) $this->db->loadResult();
    }

    private function getUserUsageCount(int $couponId, int $userId): int
    {
        if ($couponId <= 0 || $userId <= 0) {
            return 0;
        }

        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__fdshop_coupon_usage'))
            ->where($this->db->quoteName('coupon_id') . ' = ' . (int) $couponId)
            ->where($this->db->quoteName('user_id') . ' = ' . (int) $userId);

        $this->db->setQuery($query);

        return (int) $this->db->loadResult();
    }

    private function extractCouponMappings(array $data): array
    {
        $mappings = [];

        foreach (self::COUPON_MAPPING_TABLES as $key => $mapping) {
            $mappings[$key] = $this->normalizeIds($data[$key] ?? []);
        }

        return $mappings;
    }

    private function normalizeIds($ids): array
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter(
            $ids,
            static fn (int $id): bool => $id > 0
        );

        return array_values(array_unique($ids));
    }

    private function normalizeCouponCode(string $couponCode): string
    {
        return strtoupper(trim($couponCode));
    }

    private function normalizeDiscountType(string $discountType): string
    {
        $discountType = strtolower(trim($discountType));

        return $discountType === '' ? self::DISCOUNT_TYPE_PERCENT : $discountType;
    }
}
