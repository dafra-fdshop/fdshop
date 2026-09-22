<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use RuntimeException;

class ConfigurationTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__fdshop_config', 'id', $db);
    }

    public function check()
    {
        $this->id = 1;

        foreach (['image_size_default', 'image_size_small', 'image_size_mobile'] as $field) {
            if ((int) $this->$field < 1) {
                throw new RuntimeException('Die konfigurierten Bildgrößen müssen größer als 0 sein.');
            }
        }

        foreach (['image_quality_default', 'image_quality_small', 'image_quality_mobile'] as $field) {
            $quality = (int) $this->$field;
            if ($quality < 1 || $quality > 100) {
                throw new RuntimeException('Die konfigurierten WebP-Qualitäten müssen zwischen 1 und 100 liegen.');
            }
        }

        /*if (!isset($this->katalog_active) || $this->katalog_active === '' || $this->katalog_active === null) {
            $this->katalog_active = 0;
        }

        $this->katalog_active = (int) $this->katalog_active === 1 ? 1 : 0;*/

        return true;
    }
}
