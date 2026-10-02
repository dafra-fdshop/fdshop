<?php
namespace FDShop\Component\FDShop\Site\View\Favorites;
defined('_JEXEC') or die;
use FDShop\Component\FDShop\Site\Helper\FavoriteHelper;
use FDShop\Component\FDShop\Site\Helper\PurchaseHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
final class HtmlView extends BaseHtmlView{public array$lists=[];public array$items=[];public int$selected=0;public bool$purchaseEnabled=false;public function display($tpl=null):void{$app=Factory::getApplication();if($app->getIdentity()->guest){$return=base64_encode(Route::_('index.php?option=com_fdshop&view=favorites',false));$app->redirect(Route::_('index.php?option=com_users&view=login&return='.$return,false));return;}$data=$this->getModel()->getData();$this->lists=$data['lists'];$this->items=$data['items'];$this->selected=$data['selected'];$this->purchaseEnabled=PurchaseHelper::isShopEnabled();$app->getDocument()->setTitle('Meine Favoriten');$app->getDocument()->getWebAssetManager()->useStyle('com_fdshop.site')->useScript('com_fdshop.site')->useScript('com_fdshop.purchase')->useScript('com_fdshop.favorites')->useScript('com_fdshop.comparison');parent::display($tpl);}}
