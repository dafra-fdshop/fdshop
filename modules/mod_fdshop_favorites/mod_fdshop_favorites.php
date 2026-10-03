<?php

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Helper\FavoritesCounterHelper;
use FDShop\Component\FDShop\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Router\Route;

$app = Factory::getApplication();
$app->bootComponent('com_fdshop');
$state = FavoritesCounterHelper::get();
$favoritesUrl = RouteHelper::getFavoritesRoute();
$counterUrl = Route::_('index.php?option=com_fdshop&task=favorite.counter&format=json', false);
$assets = $app->getDocument()->getWebAssetManager();
$assets->getRegistry()->addRegistryFile('media/com_fdshop/joomla.asset.json');
$assets->useStyle('com_fdshop.favorites-module')->useScript('com_fdshop.favorites-module');
$module->showtitle = 0;

require ModuleHelper::getLayoutPath('mod_fdshop_favorites', $params->get('layout', 'default'));
