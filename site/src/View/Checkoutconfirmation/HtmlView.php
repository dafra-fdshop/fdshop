<?php
namespace FDShop\Component\FDShop\Site\View\Checkoutconfirmation;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
final class HtmlView extends BaseHtmlView
{
    public array $confirmation=[];
    public function display($tpl=null):void{$this->confirmation=$this->getModel()->getConfirmation()??[];if($this->confirmation===[]){throw new \RuntimeException('Die Bestellung wurde nicht gefunden.',404);}Factory::getApplication()->getDocument()->setTitle('Bestellbestätigung');parent::display($tpl);}
}
