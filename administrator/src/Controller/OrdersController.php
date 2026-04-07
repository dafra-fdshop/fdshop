<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

class OrdersController extends AdminController
{
    protected $text_prefix = 'COM_FDSHOP_ORDERS';

    protected $default_view = 'orders';

    public function getModel($name = 'Order', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}