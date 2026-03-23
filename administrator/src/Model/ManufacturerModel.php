<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use RuntimeException;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;

class ManufacturerModel extends AdminModel
{
    protected $text_prefix = 'COM_FDSHOP_MANUFACTURER';

    public function getTable($name = 'Manufacturer', $prefix = 'Table', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true): Form|false
    {
        $form = $this->loadForm(
            'com_fdshop.manufacturer',
            'manufacturer',
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
        $data = $app->getUserState('com_fdshop.edit.manufacturer.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    public function save($data): bool
    {
        $table = $this->getTable();

        if (!$table) {
            throw new RuntimeException('ManufacturerTable konnte nicht geladen werden.');
        }

        if (!empty($data['id'])) {
            $table->load((int) $data['id']);
        }

        if (!$table->bind($data)) {
            $this->setError($table->getError());

            return false;
        }

        if (!$table->check()) {
            $this->setError($table->getError());

            return false;
        }

        if (!$table->store()) {
            $this->setError($table->getError());

            return false;
        }

        $this->setState($this->getName() . '.id', (int) $table->id);

        return true;
    }
}