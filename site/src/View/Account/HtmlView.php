<?php
namespace FDShop\Component\FDShop\Site\View\Account;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
final class HtmlView extends BaseHtmlView
{
    public array $account=[];public string $section='overview';
    public function display($tpl=null):void{$app=Factory::getApplication();if($app->getIdentity()->guest){$return=base64_encode(Route::_('index.php?option=com_fdshop&view=account',false));$app->redirect(Route::_('index.php?option=com_users&view=login&return='.$return,false));return;}$this->account=$this->getModel()->getAccount();$allowed=['overview','profile','security','orders','f3','watchlist'];$section=$app->getInput()->getCmd('section','overview');$this->section=in_array($section,$allowed,true)?$section:'overview';$app->getDocument()->setTitle('Mein Konto');$app->getDocument()->getWebAssetManager()->useStyle('com_fdshop.site');parent::display($tpl);}
}
