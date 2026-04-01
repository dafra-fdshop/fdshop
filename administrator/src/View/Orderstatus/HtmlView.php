<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Orderstatus;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    public $form;

    public $item;

    public function display($tpl = null)
    {
        $model = $this->getModel();

        $this->form = $model->getForm();
        $this->item = $model->getItem();

        if ($this->item) {
            $this->item->seller_email_mode_label = $this->mapSellerEmailMode($this->item->seller_email_mode ?? null);
            $this->item->buyer_email_mode_label  = $this->mapBuyerEmailMode($this->item->buyer_email_mode ?? null);
            $this->item->create_invoice_label    = $this->mapYesNo((int) ($this->item->create_invoice ?? 0));
            $this->item->stock_action_label      = $this->mapStockAction($this->item->stock_action ?? null);
        }

        ToolbarHelper::title('FDShop - Bestellstatus');
        ToolbarHelper::apply('orderstatus.apply');
        ToolbarHelper::save('orderstatus.save');
        ToolbarHelper::cancel('orderstatus.cancel');

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