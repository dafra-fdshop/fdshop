<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Paymentmethod;

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

        ToolbarHelper::title('FDShop - Zahlungsart');
        ToolbarHelper::apply('paymentmethod.apply');
        ToolbarHelper::save('paymentmethod.save');
        ToolbarHelper::cancel('paymentmethod.cancel');

        parent::display($tpl);
    }
}