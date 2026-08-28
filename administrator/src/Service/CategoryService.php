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

class CategoryService implements CategoryServiceInterface
{
    public function __construct(
        private readonly MVCFactoryInterface $mvcFactory,
        private readonly DatabaseInterface $db
    ) {
    }

    public function saveCategory(array $data): int
    {
        $categoryName = trim((string) ($data['category_name'] ?? ''));

        if ($categoryName === '') {
            throw new InvalidArgumentException('category_name darf nicht leer sein.');
        }

        $table = $this->mvcFactory->createTable('Category', 'Administrator');

        if (!$table) {
            throw new RuntimeException('CategoryTable konnte nicht erstellt werden.');
        }

        $bindData = $data;
        $bindData['category_name'] = $categoryName;

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

    public function deleteCategories(array $categoryIds): bool
    {
        $categoryIds = $this->normalizeIds($categoryIds);

        if ($categoryIds === []) {
            return true;
        }

        foreach ($categoryIds as $categoryId) {
            $name = $this->getCategoryName($categoryId);

            if ($name === null) {
                throw new RuntimeException('Die ausgewählte Kategorie existiert nicht mehr.');
            }

            if ($this->hasReference('#__fdshop_categories', 'parent_id', $categoryId)) {
                throw new RuntimeException('Die Kategorie "' . $name . '" kann nicht gelöscht werden, weil sie Unterkategorien besitzt.');
            }

            if ($this->hasReference('#__fdshop_product_category_map', 'category_id', $categoryId)) {
                throw new RuntimeException('Die Kategorie "' . $name . '" kann nicht gelöscht werden, weil ihr Produkte zugeordnet sind.');
            }

            if ($this->hasReference('#__fdshop_coupon_category_map', 'category_id', $categoryId)) {
                throw new RuntimeException('Die Kategorie "' . $name . '" kann nicht gelöscht werden, weil sie von Gutscheinen verwendet wird.');
            }
        }

        $table = $this->mvcFactory->createTable('Category', 'Administrator');

        if (!$table) {
            throw new RuntimeException('CategoryTable konnte nicht erstellt werden.');
        }

        $this->db->transactionStart();

        try {
            foreach ($categoryIds as $categoryId) {
                if (!$table->delete($categoryId)) {
                    throw new RuntimeException($table->getError());
                }
            }

            $this->db->transactionCommit();

            return true;
        } catch (\Throwable $e) {
            $this->db->transactionRollback();
            throw $e;
        }
    }

    private function getCategoryName(int $categoryId): ?string
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('category_name'))
            ->from($this->db->quoteName('#__fdshop_categories'))
            ->where($this->db->quoteName('id') . ' = ' . $categoryId);

        $this->db->setQuery($query);
        $name = $this->db->loadResult();

        return $name === null ? null : (string) $name;
    }

    private function hasReference(string $table, string $column, int $categoryId): bool
    {
        $query = $this->db->getQuery(true)
            ->select('1')
            ->from($this->db->quoteName($table))
            ->where($this->db->quoteName($column) . ' = ' . $categoryId);

        $this->db->setQuery($query, 0, 1);

        return $this->db->loadResult() !== null;
    }

    private function normalizeIds(array $ids): array
    {
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, static fn (int $id): bool => $id > 0);

        return array_values(array_unique($ids));
    }
}
