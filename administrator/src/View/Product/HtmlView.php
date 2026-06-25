<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Product;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
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
        $model = $this->getModel();

        $this->item = $model->getItem();
        $this->form = $model->getForm();

        if (!is_object($this->item)) {
            $this->item = new stdClass();
        }

        $this->productImage = $this->getProductImage((int) ($this->item->id ?? 0));

        ToolbarHelper::title('FDShop - Produkt');
        ToolbarHelper::apply('product.apply');
        ToolbarHelper::save('product.save');
        ToolbarHelper::cancel('product.cancel');

        parent::display($tpl);
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
            ->order(
                $db->quoteName('is_primary') . ' DESC, '
                . $db->quoteName('ordering') . ' ASC, '
                . $db->quoteName('id') . ' ASC'
            );

        $db->setQuery($query, 0, 1);

        $path = $db->loadResult();

        if (!$path) {
            return null;
        }

        return (string) $path;
    }
}