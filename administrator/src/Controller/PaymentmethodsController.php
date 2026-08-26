<?php

namespace FDShop\Component\FDShop\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

class PaymentmethodsController extends AdminController
{
    protected $text_prefix = 'COM_FDSHOP_PAYMENTMETHODS';

    protected $view_list = 'configuration';

    public function getModel($name = 'Paymentmethod', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}