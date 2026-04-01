<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Configuration;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Administrator\Model\OrderstatusesModel;
use FDShop\Component\FDShop\Administrator\Model\PaymentmethodsModel;
use FDShop\Component\FDShop\Administrator\Model\ShipmentsModel;
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
        $orderstatusesModel = new OrderstatusesModel();

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

        $this->orderStatuses = $orderstatusesModel->getItems();

        foreach ($this->orderStatuses as $item) {
            $item->seller_email_mode_label = $this->mapSellerEmailMode($item->seller_email_mode ?? null);
            $item->buyer_email_mode_label  = $this->mapBuyerEmailMode($item->buyer_email_mode ?? null);
            $item->create_invoice_label    = $this->mapYesNo((int) ($item->create_invoice ?? 0));
            $item->stock_action_label      = $this->mapStockAction($item->stock_action ?? null);
        }

        ToolbarHelper::title('FDShop - Konfiguration');
        ToolbarHelper::apply('configuration.apply');
        ToolbarHelper::save('configuration.save');
        ToolbarHelper::cancel('configuration.cancel');

        parent::display($tpl);
    }

    private function mapSellerEmailMode(?string $value): string
    {
        return match ((string) $value) {
            'config' => 'Ja (Konfiguration)',
            'custom' => 'Benutzerdefiniert',
            'none'   => 'Nein',
            default  => (string) $value,
        };
    }

    private function mapBuyerEmailMode(?string $value): string
    {
        return match ((string) $value) {
            'account' => 'Ja',
            'none'    => 'Nein',
            default   => (string) $value,
        };
    }

    private function mapYesNo(int $value): string
    {
        return $value === 1 ? 'Ja' : 'Nein';
    }

    private function mapStockAction(?string $value): string
    {
        return match ((string) $value) {
            'reserve'   => 'Reservieren',
            'available' => 'Verfügbar',
            'deduct'    => 'Bestand verringern',
            'none'      => 'Keine Änderung',
            default     => (string) $value,
        };
    }
}