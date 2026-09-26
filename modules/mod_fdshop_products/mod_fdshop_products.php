<?php

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Helper\PurchaseHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

require_once __DIR__ . '/helper.php';

$data = ModFdshopProductsHelper::getData($params);
$category = $data['category'];
$items = $data['items'];

if ($category === null || $items === []) {
    return;
}

$purchaseEnabled = PurchaseHelper::isShopEnabled();
$document = Factory::getApplication()->getDocument();
$assets = $document->getWebAssetManager();
$assets->getRegistry()->addRegistryFile('media/com_fdshop/joomla.asset.json');
$assets->useStyle('com_fdshop.site')->useScript('com_fdshop.purchase')->useScript('com_fdshop.products-module');

// FDShop renders its own single, optionally linked heading so the Joomla
// module chrome cannot create a second, unlinked title above it.
$module->showtitle = 0;

require ModuleHelper::getLayoutPath('mod_fdshop_products', $params->get('layout', 'default'));
