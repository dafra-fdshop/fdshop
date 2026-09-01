<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Configuration;

defined('_JEXEC') or die;

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

    public $orderStatusState;

    public $orderStatusPagination;

    public $orderStatusFilterForm;

    public $orderStatusActiveFilters = [];

    public function display($tpl = null)
    {
        $model = $this->getModel();

        $this->form = $model->getForm();
        $this->item = $model->getItem();

        $lists = $model->getConfigurationLists();

        $this->shipments             = $lists['shipments']['items'];
        $this->shipmentState         = $lists['shipments']['state'];
        $this->shipmentPagination    = $lists['shipments']['pagination'];
        $this->shipmentFilterForm    = $lists['shipments']['filterForm'];
        $this->shipmentActiveFilters = $lists['shipments']['activeFilters'];

        $this->paymentmethods       = $lists['paymentmethods']['items'];
        $this->paymentState         = $lists['paymentmethods']['state'];
        $this->paymentPagination    = $lists['paymentmethods']['pagination'];
        $this->paymentFilterForm    = $lists['paymentmethods']['filterForm'];
        $this->paymentActiveFilters = $lists['paymentmethods']['activeFilters'];

        $this->orderStatuses            = $lists['orderstatuses']['items'];
        $this->orderStatusState         = $lists['orderstatuses']['state'];
        $this->orderStatusPagination    = $lists['orderstatuses']['pagination'];
        $this->orderStatusFilterForm    = $lists['orderstatuses']['filterForm'];
        $this->orderStatusActiveFilters = $lists['orderstatuses']['activeFilters'];

        foreach ($this->orderStatuses as $item) {
            $item->seller_email_mode_label = $this->mapSellerEmailMode($item->seller_email_mode ?? null);
            $item->notify_buyer_label      = $this->mapBuyerEmailMode($item->notify_buyer ?? 0);
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

    private function mapBuyerEmailMode(int $value): string
    {  
		return $value === 1 ? 'Ja' : 'Nein';
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
