<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class ConfigurationTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__fdshop_config', 'id', $db);
    }

    public function check()
    {
        $this->id = 1;

        if (!isset($this->katalog_active) || $this->katalog_active === '' || $this->katalog_active === null) {
            $this->katalog_active = 0;
        }

        $this->katalog_active = (int) $this->katalog_active === 1 ? 1 : 0;

        return true;
    }
}