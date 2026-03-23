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

class ManufacturerTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__fdshop_manufacturers', 'id', $db);
    }

    public function check()
    {
        $this->manufacturer_name = trim((string) $this->manufacturer_name);
        $this->alias = trim((string) ($this->alias ?? ''));

        if ($this->manufacturer_name === '') {
            $this->setError('manufacturer_name darf nicht leer sein.');

            return false;
        }

        if ($this->alias === '') {
            $this->alias = OutputFilter::stringURLSafe($this->manufacturer_name);
        } else {
            $this->alias = OutputFilter::stringURLSafe($this->alias);
        }

        if ($this->alias === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        if (!isset($this->is_active) || $this->is_active === '') {
            $this->is_active = 1;
        }

        if (!isset($this->ordering) || $this->ordering === '') {
            $this->ordering = 0;
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