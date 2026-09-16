<?php
namespace FDShop\Component\FDShop\Administrator\View\Filter;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
final class HtmlView extends BaseHtmlView
{
    public $item; public $form;
    public function display($tpl=null){$this->item=$this->getModel()->getItem();$this->form=$this->getModel()->getForm();ToolbarHelper::title('FDShop - Filter bearbeiten');ToolbarHelper::apply('filter.apply');ToolbarHelper::save('filter.save');ToolbarHelper::cancel('filter.cancel');parent::display($tpl);}
}
