<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Configuration;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Administrator\Model\PaymentMethodsModel;
use FDShop\Component\FDShop\Administrator\Model\ShipmentsModel;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $item;
    protected $shipments = [];
    protected $paymentMethods = [];

    public function display($tpl = null)
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        $shipmentsModel = new ShipmentsModel();
        $paymentMethodsModel = new PaymentMethodsModel();

        $this->shipments = $shipmentsModel->getItems();
        $this->paymentMethods = $paymentMethodsModel->getItems();

        ToolbarHelper::title('FDShop - Konfiguration');
        ToolbarHelper::apply('configuration.apply');
        ToolbarHelper::save('configuration.save');
        ToolbarHelper::cancel('configuration.cancel');

        parent::display($tpl);
    }
}