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

class ManufacturerService implements ManufacturerServiceInterface
{
    public function __construct(
        private readonly MVCFactoryInterface $mvcFactory,
        private readonly DatabaseInterface $db
    ) {
    }

    public function saveManufacturer(array $data): int
    {
        $manufacturerName = trim((string) ($data['manufacturer_name'] ?? ''));

        if ($manufacturerName === '') {
            throw new InvalidArgumentException('manufacturer_name darf nicht leer sein.');
        }

        $table = $this->mvcFactory->createTable('Manufacturer', 'Administrator');

        if (!$table) {
            throw new RuntimeException('ManufacturerTable konnte nicht erstellt werden.');
        }

        $bindData = $data;
        $bindData['manufacturer_name'] = $manufacturerName;

        if (!$table->bind($bindData)) {
            throw new RuntimeException($table->getError());
        }

        if (!$table->check()) {
            throw new RuntimeException($table->getError());
        }

        if (!$table->store()) {
            throw new RuntimeException($table->getError());
        }

        return (int) $table->id;
    }

    public function getManufacturerById(int $manufacturerId): ?object
    {
        if ($manufacturerId <= 0) {
            return null;
        }

        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__fdshop_manufacturers'))
            ->where($this->db->quoteName('id') . ' = ' . (int) $manufacturerId);

        $this->db->setQuery($query);

        $item = $this->db->loadObject();

        return $item ?: null;
    }
}