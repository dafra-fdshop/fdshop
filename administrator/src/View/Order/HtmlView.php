<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Order;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    public $item;

    public $orderItems = [];

    public $statusHistory = [];

    public $orderHistory = [];

    public function display($tpl = null)
    {
        $model = $this->getModel();

        $this->item          = $model->getItem();
        $this->orderItems    = $model->getOrderItems();
        $this->statusHistory = $model->getStatusHistory();
        $this->orderHistory  = $model->getOrderHistory();

        $title = 'FDShop - Bestellung';

        if (!empty($this->item->order_number)) {
            $title .= ' ' . $this->item->order_number;
        }

        ToolbarHelper::title($title);

        parent::display($tpl);
    }
}