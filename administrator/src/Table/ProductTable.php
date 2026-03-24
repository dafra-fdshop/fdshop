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

    public function check()
    {
        
		// Normalize datetime fields
		if (!isset($this->publish_up) || $this->publish_up === '') {
			$this->publish_up = null;
		}

		if (!isset($this->publish_down) || $this->publish_down === '') {
			$this->publish_down = null;
		}

		if (!isset($this->available_from) || $this->available_from === '') {
			$this->available_from = null;
		}
		
		$this->product_name = trim((string) $this->product_name);

        if ($this->product_name === '') {
            $this->setError('product_name darf nicht leer sein.');

            return false;
        }

        if (!isset($this->manufacturer_id) || $this->manufacturer_id === '') {
            $this->manufacturer_id = 0;
        }

        if (!isset($this->stock_quantity) || $this->stock_quantity === '') {
            $this->stock_quantity = 0;
        }

        if (!isset($this->reserved_quantity) || $this->reserved_quantity === '') {
            $this->reserved_quantity = 0;
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

        if (!isset($this->is_featured) || $this->is_featured === '') {
            $this->is_featured = 0;
        }

        if (!isset($this->access) || $this->access === '') {
            $this->access = 1;
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