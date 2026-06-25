<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use RuntimeException;

class BundleService implements BundleServiceInterface
{
    public function __construct(
        private readonly MVCFactoryInterface $mvcFactory,
        private readonly DatabaseInterface $db
    ) {
    }

    public function saveBundle(
        array $data,
        array $productIds = [],
        array $discountRules = []
    ): int {
        $bundleName = trim((string) ($data['bundle_name'] ?? ''));

        if ($bundleName === '') {
            throw new InvalidArgumentException('bundle_name darf nicht leer sein.');
        }

        if ($productIds === [] && array_key_exists('product_ids', $data)) {
            $productIds = $this->normalizeIds($data['product_ids']);
        } else {
            $productIds = $this->normalizeIds($productIds);
        }

        if ($discountRules === [] && array_key_exists('discount_rules', $data) && is_array($data['discount_rules'])) {
            $discountRules = $data['discount_rules'];
        }

        $table = $this->mvcFactory->createTable('Bundle', 'Administrator');

        if (!$table) {
            throw new RuntimeException('BundleTable konnte nicht erstellt werden.');
        }

        $bindData = $data;
        $bindData['bundle_name'] = $bundleName;
        $bindData['bundle_number'] = trim((string) ($bindData['bundle_number'] ?? ''));

        if ($bindData['bundle_number'] === '') {
            $bindData['bundle_number'] = $this->generateNextBundleNumber();
        }

        unset($bindData['product_ids'], $bindData['discount_rules']);

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

            $bundleId = (int) $table->id;

            $this->saveBundleItems($bundleId, $productIds);
            $this->saveBundleDiscountRules($bundleId, $discountRules);

            $this->db->transactionCommit();

            return $bundleId;
        } catch (\Throwable $e) {
            $this->db->transactionRollback();
            throw $e;
        }
    }

    public function saveBundleItems(int $bundleId, array $productIds): void
    {
        if ($bundleId <= 0) {
            throw new InvalidArgumentException('bundleId ist ungültig.');
        }

        $productIds = $this->normalizeIds($productIds);

        $deleteQuery = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__fdshop_bundle_items'))
            ->where($this->db->quoteName('bundle_id') . ' = ' . (int) $bundleId);

        $this->db->setQuery($deleteQuery)->execute();

        if ($productIds === []) {
            return;
        }

        foreach ($productIds as $index => $productId) {
            $table = $this->mvcFactory->createTable('BundleItem', 'Administrator');

            if (!$table) {
                throw new RuntimeException('BundleItemTable konnte nicht erstellt werden.');
            }

            $bindData = [
                'bundle_id'  => $bundleId,
                'product_id' => $productId,
                'ordering'   => $index + 1,
            ];

            if (!$table->bind($bindData)) {
                throw new RuntimeException($table->getError());
            }

            if (!$table->check()) {
                throw new RuntimeException($table->getError());
            }

            if (!$table->store()) {
                throw new RuntimeException($table->getError());
            }
        }
    }

    public function saveBundleDiscountRules(int $bundleId, array $discountRules): void
    {
        if ($bundleId <= 0) {
            throw new InvalidArgumentException('bundleId ist ungültig.');
        }

        $deleteQuery = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__fdshop_bundle_discount_rules'))
            ->where($this->db->quoteName('bundle_id') . ' = ' . (int) $bundleId);

        $this->db->setQuery($deleteQuery)->execute();

        if ($discountRules === []) {
            return;
        }

        $ordering = 1;

        foreach ($discountRules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $minQuantity = (float) ($rule['min_quantity'] ?? 0);
            $discountPercent = (float) ($rule['discount_percent'] ?? 0);

            if ($minQuantity <= 0) {
                continue;
            }

            $table = $this->mvcFactory->createTable('BundleDiscountRule', 'Administrator');

            if (!$table) {
                throw new RuntimeException('BundleDiscountRuleTable konnte nicht erstellt werden.');
            }

            $bindData = [
                'bundle_id'         => $bundleId,
                'min_quantity'      => $minQuantity,
                'discount_percent'  => $discountPercent,
                'ordering'          => (int) ($rule['ordering'] ?? $ordering),
            ];

            if (!$table->bind($bindData)) {
                throw new RuntimeException($table->getError());
            }

            if (!$table->check()) {
                throw new RuntimeException($table->getError());
            }

            if (!$table->store()) {
                throw new RuntimeException($table->getError());
            }

            $ordering++;
        }
    }

    public function generateNextBundleNumber(): string
    {
        $query = $this->db->getQuery(true)
            ->select('MAX(CAST(SUBSTRING(' . $this->db->quoteName('bundle_number') . ', 5) AS UNSIGNED))')
            ->from($this->db->quoteName('#__fdshop_bundles'))
            ->where($this->db->quoteName('bundle_number') . ' LIKE ' . $this->db->quote('BUN-%'));

        $this->db->setQuery($query);
        $maxNumber = (int) $this->db->loadResult();

        if ($maxNumber < 1000) {
            $maxNumber = 999;
        }

        return 'BUN-' . ($maxNumber + 1);
    }

    public function getBundleById(int $bundleId): ?object
    {
        if ($bundleId <= 0) {
            return null;
        }

        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__fdshop_bundles'))
            ->where($this->db->quoteName('id') . ' = ' . (int) $bundleId);

        $this->db->setQuery($query);
        $bundle = $this->db->loadObject();

        if (!$bundle) {
            return null;
        }

        $bundle->product_ids = $this->getBundleProductIds($bundleId);
        $bundle->discount_rules = $this->getBundleDiscountRules($bundleId);

        return $bundle;
    }

    public function getBestDiscountRule(int $bundleId, float $quantity): ?object
    {
        if ($bundleId <= 0 || $quantity <= 0) {
            return null;
        }

        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__fdshop_bundle_discount_rules'))
            ->where($this->db->quoteName('bundle_id') . ' = ' . (int) $bundleId)
            ->where($this->db->quoteName('min_quantity') . ' <= ' . (float) $quantity)
            ->order($this->db->quoteName('min_quantity') . ' DESC, ' . $this->db->quoteName('ordering') . ' DESC');

        $this->db->setQuery($query, 0, 1);
        $rule = $this->db->loadObject();

        return $rule ?: null;
    }

    public function findProductBySku(string $sku): ?object
    {
        $sku = trim($sku);

        if ($sku === '') {
            return null;
        }

        $query = $this->db->getQuery(true)
            ->select([
                'p.' . $this->db->quoteName('id'),
                'p.' . $this->db->quoteName('product_name'),
                'p.' . $this->db->quoteName('alias'),
                'p.' . $this->db->quoteName('is_active'),
                'p.' . $this->db->quoteName('in_stock'),
                'd.' . $this->db->quoteName('sku'),
                'd.' . $this->db->quoteName('gtin'),
            ])
            ->from($this->db->quoteName('#__fdshop_products_details', 'd'))
            ->join('INNER', $this->db->quoteName('#__fdshop_products', 'p') . ' ON p.' . $this->db->quoteName('id') . ' = d.' . $this->db->quoteName('product_id'))
            ->where('d.' . $this->db->quoteName('sku') . ' = ' . $this->db->quote($sku));

        $this->db->setQuery($query, 0, 1);
        $product = $this->db->loadObject();

        return $product ?: null;
    }

    private function getBundleProductIds(int $bundleId): array
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('product_id'))
            ->from($this->db->quoteName('#__fdshop_bundle_items'))
            ->where($this->db->quoteName('bundle_id') . ' = ' . (int) $bundleId)
            ->order($this->db->quoteName('ordering') . ' ASC, ' . $this->db->quoteName('id') . ' ASC');

        $this->db->setQuery($query);

        return array_map('intval', (array) $this->db->loadColumn());
    }

    private function getBundleDiscountRules(int $bundleId): array
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__fdshop_bundle_discount_rules'))
            ->where($this->db->quoteName('bundle_id') . ' = ' . (int) $bundleId)
            ->order($this->db->quoteName('ordering') . ' ASC, ' . $this->db->quoteName('min_quantity') . ' ASC, ' . $this->db->quoteName('id') . ' ASC');

        $this->db->setQuery($query);

        return (array) $this->db->loadObjectList();
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
}
