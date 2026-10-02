<?php
namespace FDShop\Component\FDShop\Site\View\Comparison;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
final class HtmlView extends BaseHtmlView{public array$data=[];public function display($tpl=null):void{$this->data=$this->getModel()->getData();$doc=Factory::getApplication()->getDocument();$doc->setTitle('Produktvergleich');$doc->getWebAssetManager()->useStyle('com_fdshop.site')->useScript('com_fdshop.favorites')->useScript('com_fdshop.comparison');parent::display($tpl);}}
