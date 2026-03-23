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
use FDShop\Component\FDShop\Administrator\Service\ProductService;

class ProductModel extends AdminModel
{
    protected $text_prefix = 'COM_FDSHOP_PRODUCT';

    public function getTable($name = 'Product', $prefix = 'Table', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true): Form|false
    {
        $form = $this->loadForm(
            'com_fdshop.product',
            'product',
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
        $data = $app->getUserState('com_fdshop.edit.product.data', []);

        if (!empty($data)) {
            return $data;
        }

        $item = $this->getItem();

        if (empty($item) || empty($item->id)) {
            return $item;
        }

        $service = $this->getProductService();
        $loadedItem = $service->getProductById((int) $item->id);

        if (!$loadedItem) {
            return $item;
        }

        if (
            empty($loadedItem->primary_category_id)
            && !empty($loadedItem->category_ids)
            && is_array($loadedItem->category_ids)
        ) {
            $loadedItem->primary_category_id = (int) $loadedItem->category_ids[0];
        }

        return $loadedItem;
    }

    public function save($data): bool
    {
        try {
            $categoryIds = $this->normalizeIds($data['category_ids'] ?? []);
            $buyerGroupIds = $this->normalizeIds($data['buyer_group_ids'] ?? []);
            $primaryCategoryId = !empty($data['primary_category_id'])
                ? (int) $data['primary_category_id']
                : null;

            $productId = $this->getProductService()->saveProduct(
                $data,
                $categoryIds,
                $primaryCategoryId,
                $buyerGroupIds
            );

            $this->setState($this->getName() . '.id', $productId);

            return true;
        } catch (\Throwable $e) {
            $this->setError($e->getMessage());

            return false;
        }
    }

    private function normalizeIds($ids): array
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter(
            $ids,
            static fn (int $id): bool => $id > 0
        );

        return array_values(array_unique($ids));
    }

    private function getProductService(): ProductService
    {
        $component = Factory::getApplication()->bootComponent('com_fdshop');

        return new ProductService(
            $component->getMVCFactory(),
            $this->getDatabase()
        );
    }
}