<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;

class OrderstatusModel extends AdminModel
{
    public function getTable($name = 'Orderstatus', $prefix = 'Table', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item) {
            $allowedFields = [
                'id',
                'status_code',
                'status_name',
                'seller_email_mode',
                'seller_email_address',
                'buyer_email_mode',
                'create_invoice',
                'stock_action',
            ];

            $filtered = new \stdClass();

            foreach ($allowedFields as $field) {
                $filtered->$field = $item->$field ?? null;
            }

            return $filtered;
        }

        return $item;
    }
}