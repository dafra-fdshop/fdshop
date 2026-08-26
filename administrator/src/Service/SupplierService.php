<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use RuntimeException;

class SupplierService implements SupplierServiceInterface
{
    private const ALLOWED_FIELDS = [
        'id',
        'supplier_name',
        'alias',
        'contact_name',
        'email',
        'phone',
        'website',
        'customer_number',
        'note',
        'is_active',
        'ordering',
    ];

    public function __construct(
        private readonly MVCFactoryInterface $mvcFactory,
        private readonly DatabaseInterface $db
    ) {
    }

    public function saveSupplier(array $data): int
    {
        $bindData = $this->normalizeAndValidate($data);
        $table = $this->createTable();
        $supplierId = (int) ($bindData['id'] ?? 0);

        if ($supplierId > 0 && !$table->load($supplierId)) {
            throw new RuntimeException('Lieferant konnte nicht geladen werden.');
        }

        $now = Factory::getDate()->toSql();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        if ($supplierId > 0) {
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

    public function getSupplierById(int $supplierId): ?object
    {
        if ($supplierId <= 0) {
            return null;
        }

        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__fdshop_suppliers'))
            ->where($this->db->quoteName('id') . ' = ' . $supplierId);

        $this->db->setQuery($query);
        $supplier = $this->db->loadObject();

        return $supplier ?: null;
    }

    public function changeActiveState(array $supplierIds, int $state): bool
    {
        $supplierIds = $this->normalizeIds($supplierIds);

        if ($supplierIds === []) {
            return true;
        }

        $state = $state === 1 ? 1 : 0;
        $modified = Factory::getDate()->toSql();
        $modifiedBy = (int) Factory::getApplication()->getIdentity()->id;
        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__fdshop_suppliers'))
            ->set($this->db->quoteName('is_active') . ' = ' . $state)
            ->set($this->db->quoteName('modified') . ' = ' . $this->db->quote($modified))
            ->set($this->db->quoteName('modified_by') . ' = ' . $modifiedBy)
            ->where($this->db->quoteName('id') . ' IN (' . implode(',', $supplierIds) . ')');

        $this->db->setQuery($query)->execute();

        return true;
    }

    public function deleteSuppliers(array $supplierIds): bool
    {
        $supplierIds = $this->normalizeIds($supplierIds);

        if ($supplierIds === []) {
            return true;
        }

        $table = $this->createTable();
        $this->db->transactionStart();

        try {
            foreach ($supplierIds as $supplierId) {
                if (!$table->delete($supplierId)) {
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

        $filtered['supplier_name'] = trim((string) ($filtered['supplier_name'] ?? ''));

        if ($filtered['supplier_name'] === '') {
            throw new InvalidArgumentException('Der Lieferantenname darf nicht leer sein.');
        }

        $alias = trim((string) ($filtered['alias'] ?? ''));
        $filtered['alias'] = OutputFilter::stringURLSafe($alias !== '' ? $alias : $filtered['supplier_name']);

        if ($filtered['alias'] === '') {
            $filtered['alias'] = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        foreach (['contact_name', 'email', 'phone', 'website', 'customer_number'] as $field) {
            $filtered[$field] = trim((string) ($filtered[$field] ?? ''));
        }

        $filtered['note'] = trim((string) ($filtered['note'] ?? ''));

        if ($filtered['email'] !== '' && filter_var($filtered['email'], FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Die E-Mail-Adresse ist ungültig.');
        }

        if ($filtered['website'] !== '' && filter_var($filtered['website'], FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Die Website-Adresse ist ungültig.');
        }

        $filtered['is_active'] = (int) ($filtered['is_active'] ?? 1) === 1 ? 1 : 0;
        $filtered['ordering'] = (int) ($filtered['ordering'] ?? 0);

        return $filtered;
    }

    private function normalizeIds(array $supplierIds): array
    {
        $supplierIds = array_map('intval', $supplierIds);
        $supplierIds = array_filter($supplierIds, static fn (int $id): bool => $id > 0);

        return array_values(array_unique($supplierIds));
    }

    private function createTable()
    {
        $table = $this->mvcFactory->createTable('Supplier', 'Administrator');

        if (!$table) {
            throw new RuntimeException('SupplierTable konnte nicht erstellt werden.');
        }

        return $table;
    }
}
