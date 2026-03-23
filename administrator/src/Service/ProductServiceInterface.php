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
        array $categoryIds = [],
        ?int $primaryCategoryId = null,
        array $buyerGroupIds = []
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

    public function getProductById(int $productId): ?object;
}