<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\StorageLocation;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $item;

    public function display($tpl = null)
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        ToolbarHelper::title('FDShop - Lagerort');
        ToolbarHelper::apply('storage_location.apply');
        ToolbarHelper::save('storage_location.save');
        ToolbarHelper::cancel('storage_location.cancel');

        parent::display($tpl);
    }
}
