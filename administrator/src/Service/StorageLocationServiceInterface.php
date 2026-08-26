<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Service;

defined('_JEXEC') or die;

interface StorageLocationServiceInterface
{
    public function saveStorageLocation(array $data): int;

    public function getStorageLocationById(int $storageLocationId): ?object;

    public function changeActiveState(array $storageLocationIds, int $state): bool;

    public function deleteStorageLocations(array $storageLocationIds): bool;
}
