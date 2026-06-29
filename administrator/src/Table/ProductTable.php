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

class ProductTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__fdshop_products', 'id', $db);
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
        $numericFields = [
            'manufacturer_id',
            'buyer_group_id',
            'sale_price',
            'discount_price',
            'discount_active',
            'min_order_qty',
            'max_order_qty',
            'step_order_qty',
            'is_active',
            'unit_quantity',
			'in_stock',
            'nem',
            'shot_count',
            'ribbon_new',
            'ribbon_hot',
        ];

        foreach ($numericFields as $field) {
            $value = $this->$field ?? null;

            if ($value === '' || $value === null) {
                $this->$field = 0;
            }
        }

        if (!isset($this->publish_up) || $this->publish_up === '') {
            $this->publish_up = null;
        }

        if (!isset($this->publish_down) || $this->publish_down === '') {
            $this->publish_down = null;
        }

        if (!isset($this->available_from) || $this->available_from === '') {
            $this->available_from = null;
        }

        if (empty($this->created)) {
            $this->created = Factory::getDate()->toSql();
        }

        if (empty($this->created_by)) {
            $user = Factory::getApplication()->getIdentity();
            $this->created_by = (int) $user->id;
        }

        $this->product_name = trim((string) ($this->product_name ?? ''));

        if ($this->product_name === '') {
            $this->setError('product_name darf nicht leer sein.');
            return false;
        }

        $this->alias = trim((string) ($this->alias ?? ''));
        $this->short_description = (string) ($this->short_description ?? '');
        $this->description = (string) ($this->description ?? '');

        if (!isset($this->manufacturer_id) || $this->manufacturer_id === '') {
            $this->manufacturer_id = 0;
        }

        if (!isset($this->buyer_group_id) || $this->buyer_group_id === '') {
            $this->buyer_group_id = 1;
        }

        if (!isset($this->currency) || trim((string) $this->currency) === '') {
            $this->currency = 'EUR';
        } else {
            $this->currency = strtoupper(trim((string) $this->currency));
        }

        if (!isset($this->min_order_qty) || $this->min_order_qty === '') {
            $this->min_order_qty = 0;
        }

        if (!isset($this->max_order_qty) || $this->max_order_qty === '') {
            $this->max_order_qty = 0;
        }

        if (!isset($this->step_order_qty) || $this->step_order_qty === '') {
            $this->step_order_qty = 0;
        }


        if (!isset($this->is_active) || $this->is_active === '') {
            $this->is_active = 1;
        }

        if (!isset($this->in_stock) || trim((string) $this->in_stock) === '') {
            $this->in_stock = 'Ausverkauft';
        }

        if (!isset($this->unit_type) || trim((string) $this->unit_type) === '') {
            $this->unit_type = 'Stück';
        }

        if (!isset($this->unit_quantity) || $this->unit_quantity === '') {
            $this->unit_quantity = 1;
        }

        if (!isset($this->meta_title) || $this->meta_title === null) {
            $this->meta_title = '';
        }


        if (!isset($this->meta_keywords) || $this->meta_keywords === null) {
            $this->meta_keywords = '';
        }

        if (!isset($this->meta_description) || $this->meta_description === null) {
            $this->meta_description = '';
        }

        if (!isset($this->caliber) || $this->caliber === null || $this->caliber === '') {
            $this->caliber = '0.000';
        }

        if (!isset($this->burn_time) || $this->burn_time === null || $this->burn_time === '') {
            $this->burn_time = '0.000';
        }

        if (!isset($this->rise_height) || $this->rise_height === null || $this->rise_height === '') {
            $this->rise_height = '0.000';
        }

        if (!isset($this->ribbon_new) || $this->ribbon_new === '') {
            $this->ribbon_new = 0;
        }

        if (!isset($this->ribbon_hot) || $this->ribbon_hot === '') {
            $this->ribbon_hot = 0;
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