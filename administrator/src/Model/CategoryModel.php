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
use FDShop\Component\FDShop\Administrator\Service\CategoryServiceInterface;

class CategoryModel extends AdminModel
{
    protected $text_prefix = 'COM_FDSHOP_CATEGORY';

    public function getTable($name = 'Category', $prefix = 'Table', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true): Form|false
    {
        $form = $this->loadForm(
            'com_fdshop.category',
            'category',
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
        $data = $app->getUserState('com_fdshop.edit.category.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    public function delete(&$pks): bool
    {
        if (!$this->getCurrentUser()->authorise('core.delete', 'com_fdshop')) {
            $this->setError('Sie sind nicht berechtigt, Kategorien zu löschen.');

            return false;
        }

        try {
            return $this->getCategoryService()->deleteCategories((array) $pks);
        } catch (\Throwable $e) {
            $this->setError($e->getMessage());

            return false;
        }
    }

    private function getCategoryService(): CategoryServiceInterface
    {
        $component = $this->bootComponent('com_fdshop');

        return $component->getContainer()->get(CategoryServiceInterface::class);
    }

}
