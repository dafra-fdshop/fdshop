<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Service;

defined('_JEXEC') or die;

interface ManufacturerServiceInterface
{
    public function saveManufacturer(array $data): int;

    public function getManufacturerById(int $manufacturerId): ?object;
}