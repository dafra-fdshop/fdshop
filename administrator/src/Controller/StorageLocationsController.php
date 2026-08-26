<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

class StorageLocationsController extends AdminController
{
    protected $text_prefix = 'COM_FDSHOP_STORAGE_LOCATIONS';

    protected $default_view = 'storage_locations';

    public function getModel($name = 'StorageLocation', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
