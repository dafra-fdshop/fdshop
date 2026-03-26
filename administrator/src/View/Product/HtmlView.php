<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Product;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use stdClass;

class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $item;
    protected $productImage = null;

    public function display($tpl = null)
    {
        $item = $this->get('Item');

        if (!is_object($item)) {
            $item = new stdClass();
        }

        $item->category_ids = $this->getAssignedCategoryIds((int) ($item->id ?? 0));
        $item->buyer_group_ids = $this->getAssignedBuyerGroupIds((int) ($item->id ?? 0));

        $form = Form::getInstance(
            'com_fdshop.product',
            JPATH_COMPONENT_ADMINISTRATOR . '/forms/product.xml',
            ['control' => 'jform']
        );

        $form->bind(get_object_vars($item));

        $this->item = $item;
        $this->form = $form;
        $this->productImage = $this->getProductImage((int) ($item->id ?? 0));

        ToolbarHelper::title('FDShop - Produkt');
        ToolbarHelper::apply('product.apply');
        ToolbarHelper::save('product.save');
        ToolbarHelper::cancel('product.cancel');

        parent::display($tpl);
    }

    private function getAssignedCategoryIds(int $productId): array
    {
        if ($productId <= 0) {
            return [];
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select($db->quoteName('category_id'))
            ->from($db->quoteName('#__fdshop_product_category_map'))
            ->where($db->quoteName('product_id') . ' = ' . (int) $productId)
            ->order($db->quoteName('is_primary') . ' DESC, ' . $db->quoteName('id') . ' ASC');

        $db->setQuery($query);

        return array_map('intval', (array) $db->loadColumn());
    }

    private function getAssignedBuyerGroupIds(int $productId): array
    {
        if ($productId <= 0) {
            return [];
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select($db->quoteName('buyer_group_id'))
            ->from($db->quoteName('#__fdshop_product_buyer_group_map'))
            ->where($db->quoteName('product_id') . ' = ' . (int) $productId)
            ->order($db->quoteName('id') . ' ASC');

        $db->setQuery($query);

        return array_map('intval', (array) $db->loadColumn());
    }

    private function getProductImage(int $productId): ?string
    {
        if ($productId <= 0) {
            return null;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select($db->quoteName('path_small'))
            ->from($db->quoteName('#__fdshop_media'))
            ->where($db->quoteName('product_id') . ' = ' . (int) $productId)
            ->where($db->quoteName('path_small') . " <> " . $db->quote(''))
            ->order($db->quoteName('is_primary') . ' DESC, ' . $db->quoteName('ordering') . ' ASC, ' . $db->quoteName('id') . ' ASC');

        $db->setQuery($query, 0, 1);

        $path = $db->loadResult();

        if (!$path) {
            return null;
        }

        return (string) $path;
    }
}