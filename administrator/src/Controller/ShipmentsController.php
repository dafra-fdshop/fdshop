<?php

namespace FDShop\Component\FDShop\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

class ShipmentsController extends AdminController
{
    protected $text_prefix = 'COM_FDSHOP_SHIPMENTS';

    protected $view_list = 'configuration';

    public function getModel($name = 'Shipment', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}