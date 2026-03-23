<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use RuntimeException;

class CategoryService implements CategoryServiceInterface
{
    public function __construct(
        private readonly MVCFactoryInterface $mvcFactory
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
}