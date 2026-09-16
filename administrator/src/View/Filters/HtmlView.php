<?php
namespace FDShop\Component\FDShop\Administrator\View\Filters;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
final class HtmlView extends BaseHtmlView
{
    public array $items=[];
    public function display($tpl=null){$this->items=$this->getModel()->getItems()?:[];ToolbarHelper::title('FDShop - Filter');parent::display($tpl);}
}
