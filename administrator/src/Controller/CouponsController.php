<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

class CouponsController extends AdminController
{
    protected $text_prefix = 'COM_FDSHOP_COUPONS';

    protected $default_view = 'coupons';

    public function getModel($name = 'Coupon', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
