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
use DateTimeZone;

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

        $this->account_withdrawal_days = max(1, min(365, (int) ($this->account_withdrawal_days ?? 14)));
        $this->account_f3_max_mb       = max(1, min(25, (int) ($this->account_f3_max_mb ?? 8)));
        $this->paypal_reservation_minutes = max(5, min(30, (int) ($this->paypal_reservation_minutes ?? 10)));
        $this->search_active = (int) ($this->search_active ?? 1) === 1 ? 1 : 0;
        $this->search_suggestion_limit = max(6, min(8, (int) ($this->search_suggestion_limit ?? 8)));
        $this->favorites_active = (int) ($this->favorites_active ?? 1) === 1 ? 1 : 0;
        $this->favorites_max_custom_lists = max(0, min(10, (int) ($this->favorites_max_custom_lists ?? 3)));
        $this->favorites_max_products = max(1, min(500, (int) ($this->favorites_max_products ?? 100)));
        $this->display_timezone = trim((string) ($this->display_timezone ?? 'Europe/Berlin'));
        if (!in_array($this->display_timezone, DateTimeZone::listIdentifiers(), true)) throw new RuntimeException('Bitte wählen Sie eine gültige FDShop-Anzeigezeitzone.');

        /*if (!isset($this->katalog_active) || $this->katalog_active === '' || $this->katalog_active === null) {
            $this->katalog_active = 0;
        }

        $this->katalog_active = (int) $this->katalog_active === 1 ? 1 : 0;*/

        return true;
    }
}
