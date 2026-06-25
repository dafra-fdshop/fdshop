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

class ProductDetailsTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__fdshop_products_details', 'id', $db);
    }

    public function check()
    {
        $numericFields = [
            'product_id',
            'stock_quantity',
            'low_stock',
            'reserved_quantity',
            'sold_quantity',
            'is_in_stock',
            'weight',
            'length',
            'width',
            'height',
        ];

        foreach ($numericFields as $field) {
            $value = $this->$field ?? null;

            if ($value === '' || $value === null) {
                $this->$field = 0;
            }
        }

        if ((int) ($this->product_id ?? 0) <= 0) {
            $this->setError('product_id ist ungültig.');
            return false;
        }

        $this->sku = trim((string) ($this->sku ?? ''));
        $this->gtin = trim((string) ($this->gtin ?? ''));

        if (!isset($this->stock_quantity) || $this->stock_quantity === '') {
            $this->stock_quantity = 0;
        }

        if (!isset($this->low_stock) || $this->low_stock === '') {
            $this->low_stock = 0;
        }

        if (!isset($this->reserved_quantity) || $this->reserved_quantity === '') {
            $this->reserved_quantity = 0;
        }

        if (!isset($this->sold_quantity) || $this->sold_quantity === '') {
            $this->sold_quantity = 0;
        }

        if (!isset($this->is_in_stock) || $this->is_in_stock === '') {
            $this->is_in_stock = 0;
        }

        if (!isset($this->created) || $this->created === '') {
            $this->created = Factory::getDate()->toSql();
        }

        if (!isset($this->created_by) || (int) $this->created_by <= 0) {
            $this->created_by = (int) Factory::getApplication()->getIdentity()->id;
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