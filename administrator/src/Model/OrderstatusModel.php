<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;

class OrderstatusModel extends AdminModel
{
    public function getTable($name = 'Orderstatus', $prefix = 'Table', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true): Form|false
    {
        $form = $this->loadForm(
            'com_fdshop.orderstatus',
            'orderstatus',
            [
                'control'   => 'jform',
                'load_data' => $loadData,
            ]
        );

        if (!$form) {
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_fdshop.edit.orderstatus.data', []);

        if (!empty($data)) {
            return $data;
        }

        return $this->getItem();
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