<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Service;

defined('_JEXEC') or die;

interface SupplierServiceInterface
{
    public function saveSupplier(array $data): int;

    public function getSupplierById(int $supplierId): ?object;

    public function changeActiveState(array $supplierIds, int $state): bool;

    public function deleteSuppliers(array $supplierIds): bool;
}
