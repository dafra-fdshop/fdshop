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
$document->getWebAssetManager()->useStyle('com_fdshop.site')->useScript('com_fdshop.purchase')->useScript('com_fdshop.products-module');

require ModuleHelper::getLayoutPath('mod_fdshop_products', $params->get('layout', 'default'));
