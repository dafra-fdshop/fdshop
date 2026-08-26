<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class StorageLocationTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__fdshop_storage_locations', 'id', $db);
    }

    public function getColumnAlias($column)
    {
        if ($column === 'published') {
            return 'is_active';
        }

        return parent::getColumnAlias($column);
    }
}
