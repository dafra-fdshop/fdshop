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

class ProductService implements ProductServiceInterface
{
    public function __construct(
        private readonly MVCFactoryInterface $mvcFactory,
        private readonly DatabaseInterface $db
    ) {
    }

    public function saveProduct(
        array $data,
        array $categoryIds = [],
        ?int $primaryCategoryId = null,
        array $buyerGroupIds = []
    ): int {
        $productName = trim((string) ($data['product_name'] ?? ''));

        if ($productName === '') {
            throw new InvalidArgumentException('product_name darf nicht leer sein.');
        }

        if ($categoryIds === [] && array_key_exists('category_ids', $data)) {
            $categoryIds = $this->normalizeIds($data['category_ids']);
        } else {
            $categoryIds = $this->normalizeIds($categoryIds);
        }

        if ($buyerGroupIds === [] && array_key_exists('buyer_group_ids', $data)) {
            $buyerGroupIds = $this->normalizeIds($data['buyer_group_ids']);
        } else {
            $buyerGroupIds = $this->normalizeIds($buyerGroupIds);
        }

        if ($primaryCategoryId === null && !empty($data['primary_category_id'])) {
            $primaryCategoryId = (int) $data['primary_category_id'];
        }

        $table = $this->mvcFactory->createTable('Product', 'Administrator');

        if (!$table) {
            throw new RuntimeException('ProductTable konnte nicht erstellt werden.');
        }

        $bindData = $data;
        $bindData['product_name'] = $productName;

        unset($bindData['category_ids'], $bindData['buyer_group_ids'], $bindData['primary_category_id']);

        if (!$table->bind($bindData)) {
            throw new RuntimeException($table->getError());
        }

        if (!$table->check()) {
            throw new RuntimeException($table->getError());
        }

        if (!$table->store()) {
            throw new RuntimeException($table->getError());
        }

        $productId = (int) $table->id;

        $this->saveProductCategoryAssignments($productId, $categoryIds, $primaryCategoryId);
        $this->saveProductBuyerGroupAssignments($productId, $buyerGroupIds);

        return $productId;
    }

    public function saveProductCategoryAssignments(
        int $productId,
        array $categoryIds,
        ?int $primaryCategoryId = null
    ): void {
        if ($productId <= 0) {
            throw new InvalidArgumentException('productId ist ungültig.');
        }

        $categoryIds = $this->normalizeIds($categoryIds);

        $deleteQuery = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__fdshop_product_category_map'))
            ->where($this->db->quoteName('product_id') . ' = ' . (int) $productId);

        $this->db->setQuery($deleteQuery)->execute();

        if ($categoryIds === []) {
            return;
        }

        if ($primaryCategoryId !== null && !in_array($primaryCategoryId, $categoryIds, true)) {
            $primaryCategoryId = null;
        }

        foreach ($categoryIds as $index => $categoryId) {
            $isPrimary = 0;

            if ($primaryCategoryId !== null) {
                $isPrimary = ($categoryId === $primaryCategoryId) ? 1 : 0;
            } elseif ($index === 0) {
                $isPrimary = 1;
            }

            $insertQuery = $this->db->getQuery(true)
                ->insert($this->db->quoteName('#__fdshop_product_category_map'))
                ->columns([
                    $this->db->quoteName('product_id'),
                    $this->db->quoteName('category_id'),
                    $this->db->quoteName('is_primary'),
                ])
                ->values(
                    implode(', ', [
                        (int) $productId,
                        (int) $categoryId,
                        (int) $isPrimary,
                    ])
                );

            $this->db->setQuery($insertQuery)->execute();
        }
    }

    public function saveProductBuyerGroupAssignments(
        int $productId,
        array $buyerGroupIds
    ): void {
        if ($productId <= 0) {
            throw new InvalidArgumentException('productId ist ungültig.');
        }

        $buyerGroupIds = $this->normalizeIds($buyerGroupIds);

        $deleteQuery = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__fdshop_product_buyer_group_map'))
            ->where($this->db->quoteName('product_id') . ' = ' . (int) $productId);

        $this->db->setQuery($deleteQuery)->execute();

        if ($buyerGroupIds === []) {
            return;
        }

        foreach ($buyerGroupIds as $buyerGroupId) {
            $insertQuery = $this->db->getQuery(true)
                ->insert($this->db->quoteName('#__fdshop_product_buyer_group_map'))
                ->columns([
                    $this->db->quoteName('product_id'),
                    $this->db->quoteName('buyer_group_id'),
                ])
                ->values(
                    implode(', ', [
                        (int) $productId,
                        (int) $buyerGroupId,
                    ])
                );

            $this->db->setQuery($insertQuery)->execute();
        }
    }

    public function getAssignedCategoryIds(int $productId): array
    {
        if ($productId <= 0) {
            return [];
        }

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('category_id'))
            ->from($this->db->quoteName('#__fdshop_product_category_map'))
            ->where($this->db->quoteName('product_id') . ' = ' . (int) $productId)
            ->order($this->db->quoteName('is_primary') . ' DESC, ' . $this->db->quoteName('id') . ' ASC');

        $this->db->setQuery($query);

        return array_map('intval', (array) $this->db->loadColumn());
    }

    public function getAssignedBuyerGroupIds(int $productId): array
    {
        if ($productId <= 0) {
            return [];
        }

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('buyer_group_id'))
            ->from($this->db->quoteName('#__fdshop_product_buyer_group_map'))
            ->where($this->db->quoteName('product_id') . ' = ' . (int) $productId)
            ->order($this->db->quoteName('id') . ' ASC');

        $this->db->setQuery($query);

        return array_map('intval', (array) $this->db->loadColumn());
    }

    public function getProductById(int $productId): ?object
    {
        if ($productId <= 0) {
            return null;
        }

        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__fdshop_products'))
            ->where($this->db->quoteName('id') . ' = ' . (int) $productId);

        $this->db->setQuery($query);

        $item = $this->db->loadObject();

        if (!$item) {
            return null;
        }

        $item->category_ids = $this->getAssignedCategoryIds($productId);
        $item->buyer_group_ids = $this->getAssignedBuyerGroupIds($productId);

        return $item;
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