<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Configuration;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Administrator\Model\PaymentmethodsModel;
use FDShop\Component\FDShop\Administrator\Model\ShipmentsModel;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    public $form;

    public $item;

    public $shipments = [];

    public $shipmentState;

    public $shipmentPagination;

    public $shipmentFilterForm;

    public $shipmentActiveFilters = [];

    public $paymentmethods = [];

    public $paymentState;

    public $paymentPagination;

    public $paymentFilterForm;

    public $paymentActiveFilters = [];

    public $orderStatuses = [];

    public function display($tpl = null)
    {
        $model = $this->getModel();

        $this->form = $model->getForm();
        $this->item = $model->getItem();

        $shipmentsModel = new ShipmentsModel();
        $paymentmethodsModel = new PaymentmethodsModel();

        $this->shipments              = $shipmentsModel->getItems();
        $this->shipmentState          = $shipmentsModel->getState();
        $this->shipmentPagination     = $shipmentsModel->getPagination();
        $this->shipmentFilterForm     = $shipmentsModel->getFilterForm();
        $this->shipmentActiveFilters  = $shipmentsModel->getActiveFilters();

        $this->shipmentFilterForm
            ->addControlField('task', '')
            ->addControlField('boxchecked', '0');

        $this->paymentmethods         = $paymentmethodsModel->getItems();
        $this->paymentState           = $paymentmethodsModel->getState();
        $this->paymentPagination      = $paymentmethodsModel->getPagination();
        $this->paymentFilterForm      = $paymentmethodsModel->getFilterForm();
        $this->paymentActiveFilters   = $paymentmethodsModel->getActiveFilters();

        $this->paymentFilterForm
            ->addControlField('task', '')
            ->addControlField('boxchecked', '0');

        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__fdshop_order_statuses'))
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);
        $this->orderStatuses = $db->loadObjectList();

        ToolbarHelper::title('FDShop - Konfiguration');
        ToolbarHelper::apply('configuration.apply');
        ToolbarHelper::save('configuration.save');
        ToolbarHelper::cancel('configuration.cancel');

        parent::display($tpl);
    }
}