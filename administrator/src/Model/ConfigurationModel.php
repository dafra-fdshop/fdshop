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

class ConfigurationModel extends AdminModel
{
    protected $text_prefix = 'COM_FDSHOP_CONFIGURATION';

    public function getTable($name = 'Configuration', $prefix = 'Table', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true): Form|false
    {
        $form = $this->loadForm(
            'com_fdshop.configuration',
            'configuration',
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
        $app = Factory::getApplication();
        $data = $app->getUserState('com_fdshop.edit.configuration.data', []);

        if (!empty($data)) {
            return $data;
        }

        $item = $this->getItem(1);

        if (!$item) {
            $item = (object) [
                'id'                     => 1,
                'general_vat_rate'       => '',
                'image_size_default'     => '',
                'image_size_small'       => '',
                'image_size_mobile'      => '',
                'image_size_manufacturer'=> '',
            ];
        }

        return $item;
    }

    public function getItem($pk = 1)
    {
        return parent::getItem((int) $pk);
    }

    public function save($data): bool
    {
        $data['id'] = 1;

        return parent::save($data);
    }
}