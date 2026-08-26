<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

class SuppliersController extends AdminController
{
    protected $text_prefix = 'COM_FDSHOP_SUPPLIERS';

    protected $default_view = 'suppliers';

    public function getModel($name = 'Supplier', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
