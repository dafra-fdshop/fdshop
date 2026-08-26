<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use RuntimeException;

class StorageLocationService implements StorageLocationServiceInterface
{
    private const ALLOWED_FIELDS = [
        'id',
        'location_name',
        'code',
        'description',
        'is_active',
        'ordering',
    ];

    public function __construct(
        private readonly MVCFactoryInterface $mvcFactory,
        private readonly DatabaseInterface $db
    ) {
    }

    public function saveStorageLocation(array $data): int
    {
        $bindData = $this->normalizeAndValidate($data);
        $table = $this->createTable();
        $storageLocationId = (int) ($bindData['id'] ?? 0);

        if ($storageLocationId > 0 && !$table->load($storageLocationId)) {
            throw new RuntimeException('Lagerort konnte nicht geladen werden.');
        }

        $now = Factory::getDate()->toSql();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        if ($storageLocationId > 0) {
            $bindData['modified'] = $now;
            $bindData['modified_by'] = $userId;
        } else {
            $bindData['created'] = $now;
            $bindData['created_by'] = $userId;
        }

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

    public function getStorageLocationById(int $storageLocationId): ?object
    {
        if ($storageLocationId <= 0) {
            return null;
        }

        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__fdshop_storage_locations'))
            ->where($this->db->quoteName('id') . ' = ' . $storageLocationId);

        $this->db->setQuery($query);
        $storageLocation = $this->db->loadObject();

        return $storageLocation ?: null;
    }

    public function changeActiveState(array $storageLocationIds, int $state): bool
    {
        $storageLocationIds = $this->normalizeIds($storageLocationIds);

        if ($storageLocationIds === []) {
            return true;
        }

        $state = $state === 1 ? 1 : 0;
        $modified = Factory::getDate()->toSql();
        $modifiedBy = (int) Factory::getApplication()->getIdentity()->id;
        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__fdshop_storage_locations'))
            ->set($this->db->quoteName('is_active') . ' = ' . $state)
            ->set($this->db->quoteName('modified') . ' = ' . $this->db->quote($modified))
            ->set($this->db->quoteName('modified_by') . ' = ' . $modifiedBy)
            ->where($this->db->quoteName('id') . ' IN (' . implode(',', $storageLocationIds) . ')');

        $this->db->setQuery($query)->execute();

        return true;
    }

    public function deleteStorageLocations(array $storageLocationIds): bool
    {
        $storageLocationIds = $this->normalizeIds($storageLocationIds);

        if ($storageLocationIds === []) {
            return true;
        }

        $table = $this->createTable();
        $this->db->transactionStart();

        try {
            foreach ($storageLocationIds as $storageLocationId) {
                if (!$table->delete($storageLocationId)) {
                    throw new RuntimeException($table->getError());
                }
            }

            $this->db->transactionCommit();
        } catch (\Throwable $e) {
            $this->db->transactionRollback();
            throw $e;
        }

        return true;
    }

    private function normalizeAndValidate(array $data): array
    {
        $filtered = [];

        foreach (self::ALLOWED_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $filtered[$field] = $data[$field];
            }
        }

        $filtered['location_name'] = trim((string) ($filtered['location_name'] ?? ''));

        if ($filtered['location_name'] === '') {
            throw new InvalidArgumentException('Der Lagerortname darf nicht leer sein.');
        }

        $filtered['code'] = (string) ($filtered['code'] ?? '');
        $filtered['description'] = (string) ($filtered['description'] ?? '');
        $filtered['is_active'] = (int) ($filtered['is_active'] ?? 1) === 1 ? 1 : 0;
        $filtered['ordering'] = (int) ($filtered['ordering'] ?? 0);

        return $filtered;
    }

    private function normalizeIds(array $storageLocationIds): array
    {
        $storageLocationIds = array_map('intval', $storageLocationIds);
        $storageLocationIds = array_filter(
            $storageLocationIds,
            static fn (int $id): bool => $id > 0
        );

        return array_values(array_unique($storageLocationIds));
    }

    private function createTable()
    {
        $table = $this->mvcFactory->createTable('StorageLocation', 'Administrator');

        if (!$table) {
            throw new RuntimeException('StorageLocationTable konnte nicht erstellt werden.');
        }

        return $table;
    }
}
