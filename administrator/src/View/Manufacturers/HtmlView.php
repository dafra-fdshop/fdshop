<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Manufacturers;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    protected $items = [];

    public function display($tpl = null)
    {
        $this->items = $this->get('Items');

        ToolbarHelper::title('FDShop - Hersteller');
        ToolbarHelper::addNew('manufacturer.add');

        parent::display($tpl);
    }
}