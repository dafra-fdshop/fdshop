<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

class BundlesController extends AdminController
{
    protected $text_prefix = 'COM_FDSHOP_BUNDLES';

    protected $default_view = 'bundles';

    public function getModel($name = 'Bundle', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}