<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\Filesystem\Folder;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use RuntimeException;

class ProductService implements ProductServiceInterface
{
    private const OPERATIONAL_PRODUCT_TABLES = [
        '#__fdshop_products_details',
        '#__fdshop_product_prices',
        '#__fdshop_product_prices_research',
        '#__fdshop_product_category_map',
        '#__fdshop_product_buyer_group_map',
        '#__fdshop_media',
        '#__fdshop_cart',
    ];

    public function __construct(
        private readonly MVCFactoryInterface $mvcFactory,
        private readonly DatabaseInterface $db,
        private readonly PackagingService $packagingService
    ) {
    }

    public function saveProduct(
        array $data,
        array $categoryIds,
        ?int $primaryCategoryId,
        array $buyerGroupIds,
        ?array $productImage,
        int $userId
    ): int {
        $productName = trim((string) ($data['product_name'] ?? ''));

        if ($productName === '') {
            throw new InvalidArgumentException('product_name darf nicht leer sein.');
        }

        $youtubeUrls = $this->extractYoutubeMediaUrls($data);
        $data = $this->packagingService->normalize($data);

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

        [$productData, $detailsData] = $this->splitProductData($data);
        $productData['product_name'] = $productName;
        $productData['in_stock'] = $this->calculateInStock($detailsData);

        $productTable = $this->mvcFactory->createTable('Product', 'Administrator');
        $detailsTable = $this->mvcFactory->createTable('ProductDetails', 'Administrator');

        if (!$productTable) {
            throw new RuntimeException('ProductTable konnte nicht erstellt werden.');
        }

        if (!$detailsTable) {
            throw new RuntimeException('ProductDetailsTable konnte nicht erstellt werden.');
        }

        $this->db->transactionStart();

        try {
            if (!$productTable->bind($productData)) {
                throw new RuntimeException($productTable->getError());
            }

            if (!$productTable->check()) {
                throw new RuntimeException($productTable->getError());
            }

            if (!$productTable->store()) {
                throw new RuntimeException($productTable->getError());
            }

            $productId = (int) $productTable->id;

            $detailsData['product_id'] = $productId;
            $detailsData['id'] = $this->getProductDetailsRecordId($productId);

            if (!$detailsTable->bind($detailsData)) {
                throw new RuntimeException($detailsTable->getError());
            }

            if (!$detailsTable->check()) {
                throw new RuntimeException($detailsTable->getError());
            }

            if (!$detailsTable->store()) {
                throw new RuntimeException($detailsTable->getError());
            }

            $this->saveProductCategoryAssignments($productId, $categoryIds, $primaryCategoryId);
            $this->saveProductBuyerGroupAssignments($productId, $buyerGroupIds);
            $this->saveProductFilterOptions($productId, (array) ($data['filter_option_ids'] ?? []));
            $this->processUploadedProductImage($productId, $productImage, $userId);
            $this->synchronizeYoutubeMedia($productId, $youtubeUrls, $userId);

            $this->db->transactionCommit();

            return $productId;
        } catch (\Throwable $e) {
            $this->db->transactionRollback();
            throw $e;
        }
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

        $details = $this->getProductDetailsByProductId($productId);

        if ($details) {
            foreach (get_object_vars($details) as $property => $value) {
                if ($property === 'id') {
                    $item->product_details_id = $value;
                    continue;
                }

                if ($property === 'product_id') {
                    continue;
                }

                $item->$property = $value;
            }
        } else {
            $item->product_details_id = 0;
            $item->sku = '';
            $item->gtin = '';
            $item->stock_quantity = 0;
            $item->low_stock = 0;
            $item->reserved_quantity = 0;
            $item->sold_quantity = 0;
            $item->is_in_stock = 0;
            $item->weight = 0;
            $item->length = 0;
            $item->width = 0;
            $item->height = 0;
        }

        $item->category_ids = $this->getAssignedCategoryIds($productId);
        $item->buyer_group_ids = $this->getAssignedBuyerGroupIds($productId);
        $item->filter_option_ids = [];
        foreach ($this->getFilterOptionGroups($productId) as $group) {
            foreach ($group['options'] as $option) {
                if ($option->selected) {
                    $item->filter_option_ids[] = (int) $option->id;
                }
            }
        }

        $youtubeUrls = $this->getYoutubeMediaUrls($productId);

        for ($index = 1; $index <= 3; $index++) {
            $item->{'video_' . $index} = $youtubeUrls[$index] ?? '';
        }

        return $item;
    }

    public function getFilterOptionGroups(int $productId = 0): array
    {
        $query = $this->db->getQuery(true)
            ->select(['f.filter_key', 'f.label AS group_label', 'o.id', 'o.option_key', 'o.label', 'o.is_active',
                $productId > 0 ? 'CASE WHEN pom.product_id IS NULL THEN 0 ELSE 1 END AS selected' : '0 AS selected'])
            ->from($this->db->quoteName('#__fdshop_filters', 'f'))
            ->innerJoin($this->db->quoteName('#__fdshop_filter_options', 'o') . ' ON o.filter_id=f.id');
        if ($productId > 0) {
            $query->leftJoin($this->db->quoteName('#__fdshop_product_filter_option_map', 'pom') . ' ON pom.option_id=o.id AND pom.product_id=' . $productId);
        }
        $query->whereIn('f.filter_key', ['firing_type', 'product_type'])
            ->where($productId > 0 ? '(o.is_active=1 OR pom.product_id IS NOT NULL)' : 'o.is_active=1')
            ->order('f.ordering ASC,o.ordering ASC,o.id ASC');
        $this->db->setQuery($query);
        $groups = [];
        foreach ($this->db->loadObjectList() ?: [] as $option) {
            $key = (string) $option->filter_key;
            $option->selected = (bool) $option->selected;
            $groups[$key] ??= ['key' => $key, 'label' => (string) $option->group_label, 'options' => []];
            $groups[$key]['options'][] = $option;
        }
        return array_values($groups);
    }

    private function saveProductFilterOptions(int $productId, array $ids): void
    {
        $ids = $this->normalizeIds($ids);
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('option_id'))
            ->from($this->db->quoteName('#__fdshop_product_filter_option_map'))
            ->where($this->db->quoteName('product_id') . ' = ' . $productId);
        $this->db->setQuery($query);
        $existingIds = array_map('intval', $this->db->loadColumn() ?: []);

        $query = $this->db->getQuery(true)->select('o.id')
            ->from($this->db->quoteName('#__fdshop_filter_options', 'o'))
            ->innerJoin($this->db->quoteName('#__fdshop_filters', 'f') . ' ON f.id=o.filter_id')
            ->whereIn('f.filter_key', ['firing_type', 'product_type'])
            ->where(
                '('
                . $this->db->quoteName('o.is_active') . ' = 1'
                . ($existingIds === [] ? '' : ' OR ' . $this->db->quoteName('o.id') . ' IN (' . implode(',', $existingIds) . ')')
                . ')'
            )
            ->where($ids === [] ? '1=0' : 'o.id IN (' . implode(',', $ids) . ')');
        $this->db->setQuery($query);
        $valid = array_map('intval', $this->db->loadColumn() ?: []);
        $query = $this->db->getQuery(true)->delete($this->db->quoteName('#__fdshop_product_filter_option_map'))->where('product_id=' . $productId);
        $this->db->setQuery($query)->execute();
        foreach ($valid as $optionId) {
            $query = $this->db->getQuery(true)->insert($this->db->quoteName('#__fdshop_product_filter_option_map'))
                ->columns([$this->db->quoteName('product_id'), $this->db->quoteName('option_id')])->values($productId . ',' . $optionId);
            $this->db->setQuery($query)->execute();
        }
    }

    public function trashProducts(array $productIds): bool
    {
        return $this->setDeletedState($productIds, 1, 0);
    }

    public function restoreProducts(array $productIds): bool
    {
        return $this->setDeletedState($productIds, 0, 1);
    }

    public function setPrimaryImage(int $productId, int $mediaId): void
    {
        $this->assertImageBelongsToProduct($productId, $mediaId);
        $this->db->transactionStart();

        try {
            $query = $this->db->getQuery(true)
                ->update($this->db->quoteName('#__fdshop_media'))
                ->set($this->db->quoteName('is_primary') . ' = 0')
                ->where($this->db->quoteName('product_id') . ' = ' . $productId)
                ->where($this->db->quoteName('media_type') . ' = ' . $this->db->quote('image'));
            $this->db->setQuery($query)->execute();

            $query = $this->db->getQuery(true)
                ->update($this->db->quoteName('#__fdshop_media'))
                ->set($this->db->quoteName('is_primary') . ' = 1')
                ->where($this->db->quoteName('id') . ' = ' . $mediaId)
                ->where($this->db->quoteName('product_id') . ' = ' . $productId)
                ->where($this->db->quoteName('media_type') . ' = ' . $this->db->quote('image'));
            $this->db->setQuery($query)->execute();
            $this->db->transactionCommit();
        } catch (\Throwable $e) {
            $this->db->transactionRollback();
            throw $e;
        }
    }

    public function updateImageOrdering(int $productId, int $mediaId, int $ordering): void
    {
        $this->assertImageBelongsToProduct($productId, $mediaId);
        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__fdshop_media'))
            ->set($this->db->quoteName('ordering') . ' = ' . max(0, $ordering))
            ->where($this->db->quoteName('id') . ' = ' . $mediaId)
            ->where($this->db->quoteName('product_id') . ' = ' . $productId)
            ->where($this->db->quoteName('media_type') . ' = ' . $this->db->quote('image'));
        $this->db->setQuery($query)->execute();
    }

    public function deleteProductImage(int $productId, int $mediaId): void
    {
        $medium = $this->assertImageBelongsToProduct($productId, $mediaId);
        $paths = $this->getUnsharedMediaPaths($medium, $mediaId);
        $stagedFiles = [];
        $this->db->transactionStart();

        try {
            $stagedFiles = $this->stageLocalMediaFiles($paths);
            $query = $this->db->getQuery(true)->delete($this->db->quoteName('#__fdshop_media'))
                ->where($this->db->quoteName('id') . ' = ' . $mediaId)
                ->where($this->db->quoteName('product_id') . ' = ' . $productId)
                ->where($this->db->quoteName('media_type') . ' = ' . $this->db->quote('image'));
            $this->db->setQuery($query)->execute();

            if ($this->db->getAffectedRows() !== 1) {
                throw new RuntimeException('Das Produktbild konnte nicht gelöscht werden.');
            }

            if ((int) $medium->is_primary === 1) {
                $query = $this->db->getQuery(true)->select($this->db->quoteName('id'))
                    ->from($this->db->quoteName('#__fdshop_media'))
                    ->where($this->db->quoteName('product_id') . ' = ' . $productId)
                    ->where($this->db->quoteName('media_type') . ' = ' . $this->db->quote('image'))
                    ->order($this->db->quoteName('ordering') . ' ASC, ' . $this->db->quoteName('id') . ' ASC');
                $this->db->setQuery($query, 0, 1);
                $replacementId = (int) $this->db->loadResult();
                if ($replacementId > 0) {
                    $query = $this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_media'))
                        ->set($this->db->quoteName('is_primary') . ' = 1')
                        ->where($this->db->quoteName('id') . ' = ' . $replacementId);
                    $this->db->setQuery($query)->execute();
                }
            }

            $this->db->transactionCommit();
        } catch (\Throwable $e) {
            $this->db->transactionRollback();
            $this->restoreStagedMediaFiles($stagedFiles);
            throw $e;
        }

        $this->removeStagedMediaFiles($stagedFiles);
    }

    private function assertImageBelongsToProduct(int $productId, int $mediaId): object
    {
        if ($productId < 1 || $mediaId < 1) {
            throw new InvalidArgumentException('Produkt- oder Medien-ID ist ungültig.');
        }
        $query = $this->db->getQuery(true)->select('*')
            ->from($this->db->quoteName('#__fdshop_media'))
            ->where($this->db->quoteName('id') . ' = ' . $mediaId)
            ->where($this->db->quoteName('product_id') . ' = ' . $productId)
            ->where($this->db->quoteName('media_type') . ' = ' . $this->db->quote('image'));
        $this->db->setQuery($query);
        $medium = $this->db->loadObject();
        if (!$medium) {
            throw new RuntimeException('Das gewählte Bild gehört nicht zu diesem Produkt.');
        }
        return $medium;
    }

    private function getUnsharedMediaPaths(object $medium, int $mediaId): array
    {
        $paths = [];
        foreach (['path_standard', 'path_small', 'path_mobile', 'path_invoice'] as $field) {
            $path = trim((string) ($medium->$field ?? ''));
            if ($path === '') {
                continue;
            }
            $query = $this->db->getQuery(true)->select('COUNT(*)')
                ->from($this->db->quoteName('#__fdshop_media'))
                ->where($this->db->quoteName('id') . ' <> ' . $mediaId)
                ->where('(' . implode(' OR ', array_map(
                    fn (string $column): string => $this->db->quoteName($column) . ' = ' . $this->db->quote($path),
                    ['path_standard', 'path_small', 'path_mobile', 'path_invoice']
                )) . ')');
            $this->db->setQuery($query);
            if ((int) $this->db->loadResult() === 0) {
                $paths[] = $path;
            }
        }
        return array_values(array_unique($paths));
    }

    public function permanentlyDeleteProducts(array $productIds): bool
    {
        $productIds = $this->normalizeIds($productIds);

        if ($productIds === []) {
            return true;
        }

        foreach ($productIds as $productId) {
            $product = $this->getProductDeletionRecord($productId);

            if (!$product) {
                throw new RuntimeException('Das ausgewählte Produkt existiert nicht mehr.');
            }

            if ((int) $product->is_deleted !== 1) {
                throw new RuntimeException('Das Produkt "' . $product->product_name . '" muss sich vor der endgültigen Löschung im Papierkorb befinden.');
            }

            $this->assertProductCanBePermanentlyDeleted($productId, (string) $product->product_name);
        }

        $mediaPaths = $this->getLocalMediaPaths($productIds);
        $stagedFiles = [];

        $this->db->transactionStart();

        try {
            $stagedFiles = $this->stageLocalMediaFiles($mediaPaths);

            foreach ($productIds as $productId) {
                foreach (self::OPERATIONAL_PRODUCT_TABLES as $table) {
                    $this->deleteProductRows($table, $productId);
                }

                $query = $this->db->getQuery(true)
                    ->delete($this->db->quoteName('#__fdshop_products'))
                    ->where($this->db->quoteName('id') . ' = ' . $productId)
                    ->where($this->db->quoteName('is_deleted') . ' = 1');

                $this->db->setQuery($query)->execute();

                if ($this->db->getAffectedRows() !== 1) {
                    throw new RuntimeException('Das Produkt konnte nicht endgültig gelöscht werden.');
                }
            }

            $this->db->transactionCommit();
        } catch (\Throwable $e) {
            $this->db->transactionRollback();
            $this->restoreStagedMediaFiles($stagedFiles);
            throw $e;
        }

        $this->removeStagedMediaFiles($stagedFiles);

        return true;
    }

    private function setDeletedState(array $productIds, int $isDeleted, int $expectedState): bool
    {
        $productIds = $this->normalizeIds($productIds);

        if ($productIds === []) {
            return true;
        }

        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('id'),
                $this->db->quoteName('is_deleted'),
            ])
            ->from($this->db->quoteName('#__fdshop_products'))
            ->where($this->db->quoteName('id') . ' IN (' . implode(',', $productIds) . ')');

        $this->db->setQuery($query);
        $products = (array) $this->db->loadObjectList();

        if (count($products) !== count($productIds)) {
            throw new RuntimeException('Mindestens ein ausgewähltes Produkt existiert nicht mehr.');
        }

        foreach ($products as $product) {
            if ((int) $product->is_deleted !== $expectedState) {
                throw new RuntimeException(
                    $expectedState === 1
                        ? 'Mindestens ein ausgewähltes Produkt befindet sich nicht im Papierkorb.'
                        : 'Mindestens ein ausgewähltes Produkt befindet sich bereits im Papierkorb.'
                );
            }
        }

        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__fdshop_products'))
            ->set($this->db->quoteName('is_deleted') . ' = ' . $isDeleted)
            ->where($this->db->quoteName('id') . ' IN (' . implode(',', $productIds) . ')')
            ->where($this->db->quoteName('is_deleted') . ' = ' . $expectedState);

        $this->db->setQuery($query)->execute();

        if ($this->db->getAffectedRows() !== count($productIds)) {
            throw new RuntimeException('Der Papierkorbstatus der ausgewählten Produkte hat sich zwischenzeitlich geändert.');
        }

        return true;
    }

    private function getProductDeletionRecord(int $productId): ?object
    {
        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('product_name'),
                $this->db->quoteName('is_deleted'),
            ])
            ->from($this->db->quoteName('#__fdshop_products'))
            ->where($this->db->quoteName('id') . ' = ' . $productId);

        $this->db->setQuery($query);
        $product = $this->db->loadObject();

        return $product ?: null;
    }

    private function assertProductCanBePermanentlyDeleted(int $productId, string $productName): void
    {
        $bundleQuery = $this->db->getQuery(true)
            ->select($this->db->quoteName('b.is_active'))
            ->from($this->db->quoteName('#__fdshop_bundle_items', 'bi'))
            ->join(
                'INNER',
                $this->db->quoteName('#__fdshop_bundles', 'b')
                . ' ON ' . $this->db->quoteName('b.id') . ' = ' . $this->db->quoteName('bi.bundle_id')
            )
            ->where($this->db->quoteName('bi.product_id') . ' = ' . $productId);

        $this->db->setQuery($bundleQuery);
        $bundleStates = array_map('intval', (array) $this->db->loadColumn());

        if (in_array(1, $bundleStates, true)) {
            throw new RuntimeException('Das Produkt "' . $productName . '" kann nicht endgültig gelöscht werden, weil es einem aktiven Bundle zugeordnet ist.');
        }

        if ($bundleStates !== []) {
            throw new RuntimeException('Das Produkt "' . $productName . '" kann nicht endgültig gelöscht werden, weil noch eine Bundle-Zuordnung besteht.');
        }

        if ($this->hasProductReference('#__fdshop_coupon_product_map', $productId)) {
            throw new RuntimeException('Das Produkt "' . $productName . '" kann nicht endgültig gelöscht werden, weil es von einem Gutschein verwendet wird.');
        }

        if (
            $this->hasProductReference('#__fdshop_order_items', $productId)
            || $this->hasProductReference('#__fdshop_order_bundle_items', $productId)
        ) {
            throw new RuntimeException(
                'Das Produkt "' . $productName . '" kann nicht endgültig gelöscht werden, weil historische Bestellpositionen bestehen und der aktuelle Bestell-Snapshot noch nicht vollständig unabhängig ist.'
            );
        }
    }

    private function hasProductReference(string $table, int $productId): bool
    {
        $query = $this->db->getQuery(true)
            ->select('1')
            ->from($this->db->quoteName($table))
            ->where($this->db->quoteName('product_id') . ' = ' . $productId);

        $this->db->setQuery($query, 0, 1);

        return $this->db->loadResult() !== null;
    }

    private function deleteProductRows(string $table, int $productId): void
    {
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName($table))
            ->where($this->db->quoteName('product_id') . ' = ' . $productId);

        $this->db->setQuery($query)->execute();
    }

    private function getLocalMediaPaths(array $productIds): array
    {
        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('path_standard'),
                $this->db->quoteName('path_small'),
                $this->db->quoteName('path_mobile'),
                $this->db->quoteName('path_invoice'),
            ])
            ->from($this->db->quoteName('#__fdshop_media'))
            ->where($this->db->quoteName('product_id') . ' IN (' . implode(',', $productIds) . ')')
            ->where($this->db->quoteName('media_type') . ' = ' . $this->db->quote('image'));

        $this->db->setQuery($query);
        $paths = [];

        foreach ((array) $this->db->loadAssocList() as $row) {
            foreach ($row as $path) {
                $path = trim((string) $path);

                if ($path !== '') {
                    $paths[] = $path;
                }
            }
        }

        return array_values(array_unique($paths));
    }

    private function stageLocalMediaFiles(array $paths): array
    {
        $staged = [];

        try {
            foreach ($paths as $path) {
                $relativePath = ltrim(str_replace('\\', '/', $path), '/');

                if (
                    !str_starts_with($relativePath, 'images/FDShop/products/')
                    || in_array('..', explode('/', $relativePath), true)
                    || str_contains($relativePath, "\0")
                ) {
                    throw new RuntimeException('Ein lokaler Medienpfad liegt außerhalb des freigegebenen Produktbildbereichs: ' . $path);
                }

                $source = JPATH_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

                if (!is_file($source)) {
                    continue;
                }

                $allowedRoot = realpath(JPATH_ROOT . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'FDShop' . DIRECTORY_SEPARATOR . 'products');
                $realSource = realpath($source);
                if ($allowedRoot === false || $realSource === false || !str_starts_with($realSource, $allowedRoot . DIRECTORY_SEPARATOR)) {
                    throw new RuntimeException('Ein lokaler Medienpfad verlässt den freigegebenen Produktbildbereich: ' . $path);
                }

                $temporary = $source . '.fdshop-delete-' . bin2hex(random_bytes(6));

                if (!@rename($source, $temporary)) {
                    throw new RuntimeException('Die lokale Produktbilddatei konnte nicht für die Löschung vorbereitet werden: ' . $path);
                }

                $staged[$source] = $temporary;
            }
        } catch (\Throwable $e) {
            $this->restoreStagedMediaFiles($staged);
            throw $e;
        }

        return $staged;
    }

    private function restoreStagedMediaFiles(array $stagedFiles): void
    {
        foreach (array_reverse($stagedFiles, true) as $source => $temporary) {
            if (is_file($temporary)) {
                @rename($temporary, $source);
            }
        }
    }

    private function removeStagedMediaFiles(array $stagedFiles): void
    {
        $failed = [];

        foreach ($stagedFiles as $temporary) {
            if (is_file($temporary) && !@unlink($temporary)) {
                $failed[] = $temporary;
            }
        }

        if ($failed !== []) {
            throw new RuntimeException(
                'Das Produkt wurde endgültig gelöscht, aber mindestens eine vorbereitete lokale Bilddatei konnte nicht entfernt werden.'
            );
        }
    }

    private function splitProductData(array $data): array
    {
        $productFields = [
            'id',
            'manufacturer_id',
            'product_name',
            'alias',
            'short_description',
            'description',
            'buyer_group_id',
            'sale_price',
            'discount_price',
            'discount_active',
            'currency',
            'min_order_qty',
            'max_order_qty',
            'step_order_qty',
            'is_active',
            'publish_up',
            'publish_down',
            'meta_title',
            'meta_keywords',
            'meta_description',
            'in_stock',
            'available_from',
            'unit_type',
            'nem',
            'shot_count',
            'caliber',
            'burn_time',
            'rise_height',
            'ribbon_new',
            'ribbon_hot',
        ];

        $detailFields = [
            'id',
            'product_id',
            'sku',
            'gtin',
            'bundle_eligible',
            'stock_quantity',
            'low_stock',
            'reserved_quantity',
            'sold_quantity',
            'is_in_stock',
            'created',
            'created_by',
            'modified',
            'modified_by',
            'weight',
            'length',
            'width',
            'height',
            'unit_quantity',
            'unit_discount_type',
            'unit_discount_value',
        ];

        $productData = [];
        $detailsData = [];

        foreach ($productFields as $field) {
            if (array_key_exists($field, $data)) {
                $productData[$field] = $data[$field];
            }
        }

        foreach ($detailFields as $field) {
            if (array_key_exists($field, $data)) {
                $detailsData[$field] = $data[$field];
            }
        }

        unset(
            $productData['in_stock'],
            $detailsData['id'],
            $detailsData['product_id'],
            $detailsData['created'],
            $detailsData['created_by'],
            $detailsData['modified'],
            $detailsData['modified_by']
        );

        return [$productData, $detailsData];
    }

    private function getProductDetailsByProductId(int $productId): ?object
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__fdshop_products_details'))
            ->where($this->db->quoteName('product_id') . ' = ' . (int) $productId);

        $this->db->setQuery($query);

        $details = $this->db->loadObject();

        return $details ?: null;
    }

    private function getProductDetailsRecordId(int $productId): int
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__fdshop_products_details'))
            ->where($this->db->quoteName('product_id') . ' = ' . (int) $productId);

        $this->db->setQuery($query);

        return (int) $this->db->loadResult();
    }

    private function calculateInStock(array $detailsData): string
    {
        $stockQuantity = (float) ($detailsData['stock_quantity'] ?? 0);
        $reservedQuantity = (float) ($detailsData['reserved_quantity'] ?? 0);
        $lowStock = (float) ($detailsData['low_stock'] ?? 0);
        $isInStock = (int) ($detailsData['is_in_stock'] ?? 0);

        if ($reservedQuantity >= $stockQuantity) {
            return 'Ausverkauft';
        }

        if ($reservedQuantity < $stockQuantity && $reservedQuantity < $lowStock && $isInStock === 1) {
            return 'Verfügbar';
        }

        if ($reservedQuantity < $stockQuantity && $reservedQuantity < $lowStock && $isInStock === 0) {
            return 'Bestellbar';
        }

        if ($reservedQuantity < $stockQuantity && $reservedQuantity >= $lowStock && $isInStock === 1) {
            return 'wenige Verfügbar';
        }

        if ($reservedQuantity < $stockQuantity && $reservedQuantity >= $lowStock && $isInStock === 0) {
            return 'wenige Bestellbar';
        }

        return 'Ausverkauft';
    }

    private function processUploadedProductImage(int $productId, ?array $file, int $userId): void
    {
        $file = $this->validateUploadedProductImageFile($file);

        if ($file === null) {
            return;
        }

        $this->assertGdAvailable();

        if (!is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new RuntimeException('Die hochgeladene Bilddatei ist ungültig.');
        }

        $imageInfo = @getimagesize($file['tmp_name']);

        if ($imageInfo === false || empty($imageInfo['mime'])) {
            throw new RuntimeException('Die hochgeladene Datei ist kein gültiges Bild.');
        }

        $sourceMime = (string) $imageInfo['mime'];
        $config = $this->loadImageConfiguration();
        $baseName = $this->generateMediaBaseName($productId);

        $paths = [
            'standard' => '/images/FDShop/products/standard/',
            'small'    => '/images/FDShop/products/small/',
            'mobile'   => '/images/FDShop/products/mobile/',
            'invoice'  => '/images/FDShop/products/invoices/',
        ];

        $createdFiles = [];

        try {
            $this->ensureDirectory(JPATH_ROOT . $paths['standard']);
            $this->ensureDirectory(JPATH_ROOT . $paths['small']);
            $this->ensureDirectory(JPATH_ROOT . $paths['mobile']);
            $this->ensureDirectory(JPATH_ROOT . $paths['invoice']);

            $sourceImage = $this->createImageResource($file['tmp_name'], $sourceMime);

            if (!$sourceImage) {
                throw new RuntimeException('Das Bild konnte mit GD nicht geladen werden.');
            }

            $standardFileName = $baseName . '.webp';
            $smallFileName = $baseName . '.webp';
            $mobileFileName = $baseName . '.webp';
            $invoiceFileName = $baseName . '.png';

            $standardRelativePath = $paths['standard'] . $standardFileName;
            $smallRelativePath = $paths['small'] . $smallFileName;
            $mobileRelativePath = $paths['mobile'] . $mobileFileName;
            $invoiceRelativePath = $paths['invoice'] . $invoiceFileName;

            $this->createScaledVariant(
                $sourceImage,
                (int) $config['image_size_default'],
                (int) $config['image_quality_default'],
                'image/webp',
                JPATH_ROOT . $standardRelativePath
            );
            $createdFiles[] = JPATH_ROOT . $standardRelativePath;

            $this->createScaledVariant(
                $sourceImage,
                (int) $config['image_size_small'],
                (int) $config['image_quality_small'],
                'image/webp',
                JPATH_ROOT . $smallRelativePath
            );
            $createdFiles[] = JPATH_ROOT . $smallRelativePath;

            $this->createScaledVariant(
                $sourceImage,
                (int) $config['image_size_mobile'],
                (int) $config['image_quality_mobile'],
                'image/webp',
                JPATH_ROOT . $mobileRelativePath
            );
            $createdFiles[] = JPATH_ROOT . $mobileRelativePath;

            $this->createScaledVariant(
                $sourceImage,
                (int) $config['image_size_small'],
                0,
                'image/png',
                JPATH_ROOT . $invoiceRelativePath
            );
            $createdFiles[] = JPATH_ROOT . $invoiceRelativePath;

            imagedestroy($sourceImage);

            $this->insertMediaRecord(
                $productId,
                $standardFileName,
                'image/webp',
                $standardRelativePath,
                $smallRelativePath,
                $mobileRelativePath,
                $invoiceRelativePath,
                $userId
            );
        } catch (\Throwable $e) {
            foreach ($createdFiles as $createdFile) {
                if (is_file($createdFile)) {
                    @unlink($createdFile);
                }
            }

            throw $e;
        }
    }

    private function validateUploadedProductImageFile(?array $file): ?array
    {
        if ($file === null) {
            return null;
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE || empty($file['tmp_name'])) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Der Bild-Upload ist fehlgeschlagen (Fehlercode: ' . $error . ').');
        }

        return $file;
    }

    private function assertGdAvailable(): void
    {
        if (!extension_loaded('gd') || !function_exists('gd_info')) {
            throw new RuntimeException('Die PHP-Erweiterung GD ist nicht verfügbar. Bildverarbeitung ist nicht möglich.');
        }
    }

    private function loadImageConfiguration(): array
    {
        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('image_size_default'),
                $this->db->quoteName('image_quality_default'),
                $this->db->quoteName('image_size_small'),
                $this->db->quoteName('image_quality_small'),
                $this->db->quoteName('image_size_mobile'),
                $this->db->quoteName('image_quality_mobile'),
            ])
            ->from($this->db->quoteName('#__fdshop_config'))
            ->where($this->db->quoteName('id') . ' = 1');

        $this->db->setQuery($query);
        $config = (array) $this->db->loadAssoc();

        return [
            'image_size_default'    => max(1, (int) ($config['image_size_default'] ?? 550)),
            'image_quality_default' => min(100, max(1, (int) ($config['image_quality_default'] ?? 80))),
            'image_size_small'      => max(1, (int) ($config['image_size_small'] ?? 200)),
            'image_quality_small'   => min(100, max(1, (int) ($config['image_quality_small'] ?? 60))),
            'image_size_mobile'     => max(1, (int) ($config['image_size_mobile'] ?? 250)),
            'image_quality_mobile'  => min(100, max(1, (int) ($config['image_quality_mobile'] ?? 70))),
        ];
    }

    private function generateMediaBaseName(int $productId): string
    {
        $random = bin2hex(random_bytes(6));

        return 'product-' . $productId . '-' . $random;
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!Folder::create($directory)) {
            throw new RuntimeException('Das Verzeichnis konnte nicht erstellt werden: ' . $directory);
        }
    }

    private function createImageResource(string $tmpFile, string $mime)
    {
        $content = @file_get_contents($tmpFile);

        if ($content === false) {
            throw new RuntimeException('Die hochgeladene Bilddatei konnte nicht gelesen werden.');
        }

        $image = @imagecreatefromstring($content);

        if ($image === false) {
            throw new RuntimeException('Die hochgeladene Bilddatei wird nicht unterstützt.');
        }

        return $image;
    }

    private function createScaledVariant($sourceImage, int $targetSize, int $quality, string $outputMime, string $targetPath): void
    {
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            throw new RuntimeException('Ungültige Bildabmessungen.');
        }

        $maxWidth = (float) $targetSize;
        $maxHeight = $targetSize * (485 / 550);
        $maxArea = ($targetSize * $targetSize) * (170000 / (550 * 550));
        $scale = min(
            1,
            $maxWidth / $sourceWidth,
            $maxHeight / $sourceHeight,
            sqrt($maxArea / ($sourceWidth * $sourceHeight))
        );
        $targetWidth = max(1, (int) floor($sourceWidth * $scale));
        $targetHeight = max(1, (int) floor($sourceHeight * $scale));

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($targetImage === false) {
            throw new RuntimeException('Die Bildvariante konnte nicht erzeugt werden.');
        }

        $this->prepareTargetCanvas($targetImage, $outputMime);

        if (!imagecopyresampled(
            $targetImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        )) {
            imagedestroy($targetImage);
            throw new RuntimeException('Die Bildskalierung ist fehlgeschlagen.');
        }

        $saved = $this->saveImageResource($targetImage, $outputMime, $targetPath, $quality);

        imagedestroy($targetImage);

        if (!$saved) {
            throw new RuntimeException('Die Bilddatei konnte nicht gespeichert werden: ' . $targetPath);
        }
    }

    private function prepareTargetCanvas($image, string $mime): void
    {
        if (in_array($mime, ['image/png', 'image/gif', 'image/webp'], true)) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);

            return;
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $white);
    }

    private function saveImageResource($image, string $mime, string $targetPath, int $quality = 80): bool
    {
        return match ($mime) {
            'image/jpeg', 'image/pjpeg' => imagejpeg($image, $targetPath, 90),
            'image/png'                 => imagepng($image, $targetPath, 6),
            'image/gif'                 => imagegif($image, $targetPath),
            'image/webp'                => function_exists('imagewebp')
                ? imagewebp($image, $targetPath, $quality)
                : throw new RuntimeException('WEBP wird von GD auf diesem Server nicht unterstützt.'),
            default                     => throw new RuntimeException('Nicht unterstütztes Bildformat: ' . $mime),
        };
    }

    private function insertMediaRecord(
        int $productId,
        string $fileName,
        string $fileType,
        string $pathStandard,
        string $pathSmall,
        string $pathMobile,
        string $pathInvoice,
        int $userId
    ): void {
        $created = Factory::getDate()->toSql();

        $query = $this->db->getQuery(true)
            ->select([
                'COUNT(*) AS media_count',
                'COALESCE(MAX(' . $this->db->quoteName('ordering') . '), 0) AS max_ordering',
            ])
            ->from($this->db->quoteName('#__fdshop_media'))
            ->where($this->db->quoteName('product_id') . ' = ' . (int) $productId)
            ->where($this->db->quoteName('media_type') . ' = ' . $this->db->quote('image'));

        $this->db->setQuery($query);
        $stats = (array) $this->db->loadAssoc();

        $isPrimary = ((int) ($stats['media_count'] ?? 0) === 0) ? 1 : 0;
        $ordering = (int) ($stats['max_ordering'] ?? 0) + 1;

        $insertQuery = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__fdshop_media'))
            ->columns([
                $this->db->quoteName('product_id'),
                $this->db->quoteName('file_name'),
                $this->db->quoteName('file_type'),
                $this->db->quoteName('path_standard'),
                $this->db->quoteName('path_small'),
                $this->db->quoteName('path_mobile'),
                $this->db->quoteName('path_invoice'),
                $this->db->quoteName('is_primary'),
                $this->db->quoteName('ordering'),
                $this->db->quoteName('created'),
                $this->db->quoteName('created_by'),
                $this->db->quoteName('media_type'),
                $this->db->quoteName('external_url'),
            ])
            ->values(
                implode(', ', [
                    (int) $productId,
                    $this->db->quote($fileName),
                    $this->db->quote($fileType),
                    $this->db->quote($pathStandard),
                    $this->db->quote($pathSmall),
                    $this->db->quote($pathMobile),
                    $this->db->quote($pathInvoice),
                    (int) $isPrimary,
                    (int) $ordering,
                    $this->db->quote($created),
                    (int) $userId,
                    $this->db->quote('image'),
                    'NULL',
                ])
            );

        $this->db->setQuery($insertQuery)->execute();
    }

    private function getYoutubeMediaUrls(int $productId): array
    {
        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('external_url'),
                $this->db->quoteName('ordering'),
            ])
            ->from($this->db->quoteName('#__fdshop_media'))
            ->where($this->db->quoteName('product_id') . ' = ' . (int) $productId)
            ->where($this->db->quoteName('media_type') . ' = ' . $this->db->quote('youtube'))
            ->order($this->db->quoteName('ordering') . ' ASC, ' . $this->db->quoteName('id') . ' ASC');

        $this->db->setQuery($query, 0, 3);

        $urls = [];

        foreach ((array) $this->db->loadObjectList() as $row) {
            $position = (int) ($row->ordering ?? 0);

            if ($position >= 1 && $position <= 3 && !isset($urls[$position])) {
                $urls[$position] = (string) ($row->external_url ?? '');
            }
        }

        return $urls;
    }

    private function extractYoutubeMediaUrls(array $data): array
    {
        $urls = [];

        for ($index = 1; $index <= 3; $index++) {
            $input = trim((string) ($data['video_' . $index] ?? ''));

            if ($input !== '') {
                $urls[$index] = $this->extractYoutubeEmbedUrl($input);
            }
        }

        return $urls;
    }

    private function synchronizeYoutubeMedia(int $productId, array $urls, int $userId): void
    {

        $deleteQuery = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__fdshop_media'))
            ->where($this->db->quoteName('product_id') . ' = ' . (int) $productId)
            ->where($this->db->quoteName('media_type') . ' = ' . $this->db->quote('youtube'));

        $this->db->setQuery($deleteQuery)->execute();

        if ($urls === []) {
            return;
        }

        $created = Factory::getDate()->toSql();

        foreach ($urls as $position => $url) {
            $insertQuery = $this->db->getQuery(true)
                ->insert($this->db->quoteName('#__fdshop_media'))
                ->columns([
                    $this->db->quoteName('product_id'),
                    $this->db->quoteName('media_type'),
                    $this->db->quoteName('external_url'),
                    $this->db->quoteName('is_primary'),
                    $this->db->quoteName('ordering'),
                    $this->db->quoteName('created'),
                    $this->db->quoteName('created_by'),
                ])
                ->values(implode(', ', [
                    (int) $productId,
                    $this->db->quote('youtube'),
                    $this->db->quote($url),
                    0,
                    (int) $position,
                    $this->db->quote($created),
                    $userId,
                ]));

            $this->db->setQuery($insertQuery)->execute();
        }
    }

    private function extractYoutubeEmbedUrl(string $input): string
    {
        $url = $input;

        if (stripos($input, '<iframe') !== false) {
            if (!preg_match('/\\bsrc\\s*=\\s*(["\'])(.*?)\\1/is', $input, $matches)) {
                throw new InvalidArgumentException('Der YouTube-Embed-Code enthält keine gültige src-URL.');
            }

            $url = $matches[2];
        }

        $url = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if (
            ($parts['scheme'] ?? '') !== 'https'
            || !in_array($host, ['youtube.com', 'www.youtube.com'], true)
            || !preg_match('~^/embed/[^/]+$~', $path)
        ) {
            throw new InvalidArgumentException('Bitte einen gültigen YouTube-Embed-Code oder eine YouTube-Embed-URL eingeben.');
        }

        return $url;
    }

    private function getExtensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg', 'image/pjpeg' => 'jpg',
            'image/png'                 => 'png',
            'image/gif'                 => 'gif',
            'image/webp'                => 'webp',
            default                     => throw new RuntimeException('Nicht unterstütztes Bildformat: ' . $mime),
        };
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
