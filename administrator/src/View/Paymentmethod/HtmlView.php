<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Paymentmethod;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $item;
    public array $paypalAdmin = [];
    public bool $canManageCredentials = false;
    public string $paypalWebhookUrl = '';

    public function display($tpl = null)
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->canManageCredentials = Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_fdshop');
        if ($this->canManageCredentials) {
            $this->paypalAdmin = $this->getModel()->getPayPalAdminData();
        }
        $this->paypalWebhookUrl = rtrim(Uri::root(), '/') . '/index.php?option=com_fdshop&task=payment.webhook&format=json';

        ToolbarHelper::title('FDShop - Zahlungsart');
        ToolbarHelper::apply('paymentmethod.apply');
        ToolbarHelper::save('paymentmethod.save');
        ToolbarHelper::cancel('paymentmethod.cancel');

        parent::display($tpl);
    }
}
