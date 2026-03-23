<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Categories;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    protected $items = [];

    public function display($tpl = null)
    {
        $this->items = $this->get('Items');

        ToolbarHelper::title('FDShop - Kategorien');
        ToolbarHelper::addNew('category.add');

        parent::display($tpl);
    }
}