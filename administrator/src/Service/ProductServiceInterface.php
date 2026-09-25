<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Service;

defined('_JEXEC') or die;

interface ProductServiceInterface
{
    public function saveProduct(
        array $data,
        array $categoryIds,
        ?int $primaryCategoryId,
        array $buyerGroupIds,
        ?array $productImage,
        int $userId
    ): int;

    public function saveProductCategoryAssignments(
        int $productId,
        array $categoryIds,
        ?int $primaryCategoryId = null
    ): void;

    public function saveProductBuyerGroupAssignments(
        int $productId,
        array $buyerGroupIds
    ): void;

    public function getAssignedCategoryIds(int $productId): array;

    public function getAssignedBuyerGroupIds(int $productId): array;

    public function getFilterOptionGroups(int $productId = 0): array;

    public function getProductById(int $productId): ?object;

    public function trashProducts(array $productIds): bool;

    public function restoreProducts(array $productIds): bool;

    public function permanentlyDeleteProducts(array $productIds): bool;

    public function setPrimaryImage(int $productId, int $mediaId): void;

    public function updateImageOrdering(int $productId, int $mediaId, int $ordering): void;

    public function deleteProductImage(int $productId, int $mediaId): void;

    public function importProductImageFromLocalFile(int $productId, string $sourcePath, int $userId): int;

    public function recalculateStockStatus(array $productIds): void;
}
