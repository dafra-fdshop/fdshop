<?php

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Helper\CartSummaryHelper;
use FDShop\Component\FDShop\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Router\Route;

$app = Factory::getApplication();
$app->bootComponent('com_fdshop');
$summary = CartSummaryHelper::get();
$cartUrl = RouteHelper::getCartRoute();
$summaryUrl = Route::_('index.php?option=com_fdshop&task=cart.summary&format=json', false);
$assets = $app->getDocument()->getWebAssetManager();
$assets->getRegistry()->addRegistryFile('media/com_fdshop/joomla.asset.json');
$assets->useStyle('com_fdshop.cart-module')->useScript('com_fdshop.cart-module');
$module->showtitle = 0;

require ModuleHelper::getLayoutPath('mod_fdshop_cart', $params->get('layout', 'default'));
