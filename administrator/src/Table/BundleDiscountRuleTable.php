<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class BundleDiscountRuleTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__fdshop_bundle_discount_rules', 'id', $db);
    }

    public function check()
    {
        if ((int) ($this->bundle_id ?? 0) <= 0) {
            $this->setError('bundle_id ist ungültig.');
            return false;
        }

        if (!isset($this->min_quantity) || $this->min_quantity === '') {
            $this->min_quantity = 1;
        }

        if ((float) $this->min_quantity <= 0) {
            $this->setError('min_quantity muss größer als 0 sein.');
            return false;
        }

        if (!isset($this->discount_percent) || $this->discount_percent === '') {
            $this->discount_percent = 0;
        }

        if ((float) $this->discount_percent < 0) {
            $this->setError('discount_percent darf nicht negativ sein.');
            return false;
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
