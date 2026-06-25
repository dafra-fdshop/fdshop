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

class BundleTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__fdshop_bundles', 'id', $db);
    }

    public function getColumnAlias($column)
    {
        if ($column === 'published') {
            return 'is_active';
        }

        return parent::getColumnAlias($column);
    }

    public function check()
    {
        $this->bundle_number = trim((string) ($this->bundle_number ?? ''));
        $this->bundle_name = trim((string) ($this->bundle_name ?? ''));
        $this->alias = trim((string) ($this->alias ?? ''));

        if ($this->bundle_number === '') {
            $this->setError('bundle_number darf nicht leer sein.');
            return false;
        }

        if ($this->bundle_name === '') {
            $this->setError('bundle_name darf nicht leer sein.');
            return false;
        }

        if ($this->alias === '') {
            $this->alias = OutputFilter::stringURLSafe($this->bundle_name);
        } else {
            $this->alias = OutputFilter::stringURLSafe($this->alias);
        }

        if ($this->alias === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        if (!isset($this->is_active) || $this->is_active === '') {
            $this->is_active = 0;
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
