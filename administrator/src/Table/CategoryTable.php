<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class CategoryTable extends Table
{

    public function getColumnAlias($column)
    {
        if ($column === 'published') {
            return 'is_active';
        }

        return parent::getColumnAlias($column);
    }

    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__fdshop_categories', 'id', $db);
    }

    public function check()
    {
        $this->category_name = trim((string) $this->category_name);
        $this->alias = trim((string) ($this->alias ?? ''));

        if ($this->category_name === '') {
            $this->setError('category_name darf nicht leer sein.');

            return false;
        }

        if ($this->alias === '') {
            $this->alias = OutputFilter::stringURLSafe($this->category_name);
        } else {
            $this->alias = OutputFilter::stringURLSafe($this->alias);
        }

        if ($this->alias === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        if (!isset($this->parent_id) || $this->parent_id === '') {
            $this->parent_id = 0;
        }

        if (!isset($this->level) || $this->level === '') {
            $this->level = 1;
        }

        if (!isset($this->ordering) || $this->ordering === '') {
            $this->ordering = 0;
        }

        if (!isset($this->is_active) || $this->is_active === '') {
            $this->is_active = 1;
        }

        return true;
    }

    public function store($updateNulls = true)
    {
        $date = Factory::getDate()->toSql();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        if ((int) $this->id > 0) {
            $this->modified = $date;
            $this->modified_by = $userId;
        } else {
            if (empty($this->created)) {
                $this->created = $date;
            }

            if (empty($this->created_by)) {
                $this->created_by = $userId;
            }
        }

        return parent::store($updateNulls);
    }
}
