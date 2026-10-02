<?php
defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Helper\SearchHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

$app = Factory::getApplication();
$app->bootComponent('com_fdshop');

if (!SearchHelper::enabled()) {
    return;
}

$assets = $app->getDocument()->getWebAssetManager();
$assets->getRegistry()->addRegistryFile('media/com_fdshop/joomla.asset.json');
$assets->useStyle('com_fdshop.site')->useScript('com_fdshop.search');

require ModuleHelper::getLayoutPath('mod_fdshop_search', $params->get('layout', 'default'));
