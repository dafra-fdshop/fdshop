<?php
namespace FDShop\Component\FDShop\Administrator\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\AdminController;
final class FiltersController extends AdminController
{
    protected $default_view='filters';
    public function getModel($name='Filter',$prefix='Administrator',$config=['ignore_request'=>true]){return parent::getModel($name,$prefix,$config);}
}
