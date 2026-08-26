<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Administrator\Service\StorageLocationServiceInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;

class StorageLocationModel extends AdminModel
{
    protected $text_prefix = 'COM_FDSHOP_STORAGE_LOCATION';

    public function getTable($name = 'StorageLocation', $prefix = 'Table', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true): Form|false
    {
        return $this->loadForm(
            'com_fdshop.storage_location',
            'storage_location',
            [
                'control' => 'jform',
                'load_data' => $loadData,
            ]
        );
    }

    protected function loadFormData()
    {
        $app = Factory::getApplication();
        $data = $app->getUserState('com_fdshop.edit.storage_location.data', []);

        return empty($data) ? $this->getItem() : $data;
    }

    public function getItem($pk = null)
    {
        $storageLocationId = $pk !== null
            ? (int) $pk
            : Factory::getApplication()->input->getInt('id');

        if ($storageLocationId <= 0) {
            return parent::getItem($pk);
        }

        return $this->getStorageLocationService()->getStorageLocationById($storageLocationId)
            ?: parent::getItem($pk);
    }

    public function save($data): bool
    {
        try {
            $storageLocationId = $this->getStorageLocationService()->saveStorageLocation((array) $data);
            $this->setState($this->getName() . '.id', $storageLocationId);

            return true;
        } catch (\Throwable $e) {
            $this->setError($e->getMessage());

            return false;
        }
    }

    public function publish(&$pks, $value = 1): bool
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_fdshop')) {
            $this->setError(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));

            return false;
        }

        try {
            return $this->getStorageLocationService()->changeActiveState((array) $pks, (int) $value);
        } catch (\Throwable $e) {
            $this->setError($e->getMessage());

            return false;
        }
    }

    public function delete(&$pks): bool
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_fdshop')) {
            $this->setError(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'));

            return false;
        }

        try {
            return $this->getStorageLocationService()->deleteStorageLocations((array) $pks);
        } catch (\Throwable $e) {
            $this->setError($e->getMessage());

            return false;
        }
    }

    private function getStorageLocationService(): StorageLocationServiceInterface
    {
        $component = Factory::getApplication()->bootComponent('com_fdshop');

        return $component->getContainer()->get(StorageLocationServiceInterface::class);
    }
}
