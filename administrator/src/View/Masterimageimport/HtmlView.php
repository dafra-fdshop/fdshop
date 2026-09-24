<?php
namespace FDShop\Component\FDShop\Administrator\View\Masterimageimport;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
final class HtmlView extends BaseHtmlView
{
    public array $data=[];
    public function display($tpl=null){if(!$this->getCurrentUser()->authorise('core.manage','com_fdshop'))throw new \RuntimeException('Keine Berechtigung.',403);$this->data=$this->getModel()->getPageData();ToolbarHelper::title('FDShop Tools: Master-Bildimport');parent::display($tpl);}
}
