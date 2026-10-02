<?php
namespace FDShop\Component\FDShop\Site\View\Search;
defined('_JEXEC') or die;
use FDShop\Component\FDShop\Site\Helper\PurchaseHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
final class HtmlView extends BaseHtmlView{public array $items=[];public object $pagination;public object $state;public bool $purchaseEnabled=false;public function display($tpl=null):void{$m=$this->getModel();$this->items=$m->getItems();$this->pagination=$m->getPagination();$this->state=$m->getState();$q=(string)$this->state->get('search.query','');foreach(['option'=>'com_fdshop','view'=>'search','q'=>$q,'limit'=>(int)$this->state->get('list.limit',24)] as $k=>$v)$this->pagination->setAdditionalUrlParam($k,$v);$this->purchaseEnabled=PurchaseHelper::isShopEnabled();Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('com_fdshop.site')->useScript('com_fdshop.site')->useScript('com_fdshop.purchase')->useScript('com_fdshop.search');parent::display($tpl);}}
