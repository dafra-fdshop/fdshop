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
use RuntimeException;

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
                'id'                      => 1,
                'general_vat_rate'        => '',
				'general_currency'		  => '',
                'katalog_active'          => '',
                'image_size_default'      => '',
                'image_size_small'        => '',
                'image_size_mobile'       => '',
                'image_size_manufacturer' => '',
                'show_terms_checkbox'     => 0,
                'require_terms_checkbox'  => 0,
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
        //$data['katalog_active'] = !empty($data['katalog_active']) ? 1 : 0;

        return parent::save($data);
    }

    public function getConfigurationLists(): array
    {
        return [
            'shipments'      => $this->getConfigurationListData('Shipments'),
            'paymentmethods' => $this->getConfigurationListData('PaymentMethods'),
            'orderstatuses'  => $this->getConfigurationListData('Orderstatuses'),
        ];
    }

    private function getConfigurationListData(string $modelName): array
    {
        $model = $this->getMVCFactory()->createModel($modelName, 'Administrator');

        if (!$model instanceof ConfigurationListModel) {
            throw new RuntimeException($modelName . 'Model konnte nicht erstellt werden.');
        }

        $filterForm = $model->getFilterForm();

        if ($filterForm instanceof Form) {
            $filterForm
                ->addControlField('option', 'com_fdshop')
                ->addControlField('task', '')
                ->addControlField('boxchecked', '0');
        }

        return [
            'items'         => $model->getItems(),
            'state'         => $model->getState(),
            'pagination'    => $model->getPagination(),
            'filterForm'    => $filterForm,
            'activeFilters' => $model->getActiveFilters(),
        ];
    }
}
