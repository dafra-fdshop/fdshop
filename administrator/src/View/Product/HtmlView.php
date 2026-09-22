<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Product;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use stdClass;

class HtmlView extends BaseHtmlView
{
    protected $form;

    protected $item;

    protected array $productImages = [];

    protected array $filterOptionGroups = [];

    public function display($tpl = null)
    {
        $model = $this->getModel();

        $this->item = $model->getItem();
        $this->form = $model->getForm();

        if (!is_object($this->item)) {
            $this->item = new stdClass();
        }

        $this->productImages = $model->getProductImages((int) ($this->item->id ?? 0));
        $this->filterOptionGroups = $model->getFilterOptionGroups();

        ToolbarHelper::title('FDShop - Produkt');
        ToolbarHelper::apply('product.apply');
        ToolbarHelper::save('product.save');
        ToolbarHelper::cancel('product.cancel');

        parent::display($tpl);
    }
}
