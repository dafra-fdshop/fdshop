<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Administrator\Service\SupplierServiceInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;

class SupplierModel extends AdminModel
{
    protected $text_prefix = 'COM_FDSHOP_SUPPLIER';

    public function getTable($name = 'Supplier', $prefix = 'Table', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true): Form|false
    {
        return $this->loadForm(
            'com_fdshop.supplier',
            'supplier',
            [
                'control' => 'jform',
                'load_data' => $loadData,
            ]
        );
    }

    protected function loadFormData()
    {
        $app = Factory::getApplication();
        $data = $app->getUserState('com_fdshop.edit.supplier.data', []);

        return empty($data) ? $this->getItem() : $data;
    }

    public function getItem($pk = null)
    {
        $supplierId = $pk !== null
            ? (int) $pk
            : Factory::getApplication()->input->getInt('id');

        if ($supplierId <= 0) {
            return parent::getItem($pk);
        }

        return $this->getSupplierService()->getSupplierById($supplierId) ?: parent::getItem($pk);
    }

    public function save($data): bool
    {
        try {
            $supplierId = $this->getSupplierService()->saveSupplier((array) $data);
            $this->setState($this->getName() . '.id', $supplierId);

            return true;
        } catch (\Throwable $e) {
            $this->setError($e->getMessage());

            return false;
        }
    }

    public function publish(&$pks, $value = 1): bool
    {
        try {
            return $this->getSupplierService()->changeActiveState((array) $pks, (int) $value);
        } catch (\Throwable $e) {
            $this->setError($e->getMessage());

            return false;
        }
    }

    public function delete(&$pks): bool
    {
        try {
            return $this->getSupplierService()->deleteSuppliers((array) $pks);
        } catch (\Throwable $e) {
            $this->setError($e->getMessage());

            return false;
        }
    }

    private function getSupplierService(): SupplierServiceInterface
    {
        $component = Factory::getApplication()->bootComponent('com_fdshop');

        return $component->getContainer()->get(SupplierServiceInterface::class);
    }
}
