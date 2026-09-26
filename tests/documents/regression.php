<?php
declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_fdshop/src/Extension/FdshopComponent.php';
require_once JPATH_ADMINISTRATOR . '/components/com_fdshop/src/Service/OrderDocumentService.php';

use FDShop\Component\FDShop\Administrator\Service\OrderDocumentService;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Session\SessionInterface;

$_SERVER['HTTP_HOST']='localhost';$_SERVER['REQUEST_URI']='/';$_SERVER['SCRIPT_NAME']='/index.php';
$container=Factory::getContainer();$container->alias(SessionInterface::class,'session.web.site');$app=$container->get(SiteApplication::class);Factory::$application=$app;
$service=$app->bootComponent('com_fdshop')->getContainer()->get(OrderDocumentService::class);$db=$container->get(DatabaseInterface::class);$orderId=(int)($argv[1]??900800);
$first=$service->customerDocument($orderId,true);$second=$service->customerDocument($orderId,true);
if(!hash_equals(hash('sha256',$first['bytes']),hash('sha256',$second['bytes'])))throw new RuntimeException('Archived customer PDF is not byte-identical.');
$db->setQuery($db->getQuery(true)->select('document_company_name')->from($db->quoteName('#__fdshop_config'))->where('id=1'));$company=(string)$db->loadResult();
$db->setQuery($db->getQuery(true)->update($db->quoteName('#__fdshop_config'))->set('document_company_name='.$db->quote('Changed after archive'))->where('id=1'))->execute();
$afterConfig=$service->customerDocument($orderId,true);
$db->setQuery($db->getQuery(true)->update($db->quoteName('#__fdshop_config'))->set('document_company_name='.$db->quote($company))->where('id=1'))->execute();
if(!hash_equals(hash('sha256',$first['bytes']),hash('sha256',$afterConfig['bytes'])))throw new RuntimeException('Configuration changed archived PDF.');

$db->setQuery($db->getQuery(true)->select('shipment_id')->from($db->quoteName('#__fdshop_orders'))->where('id='.$orderId));$shipmentId=(int)$db->loadResult();
$db->setQuery($db->getQuery(true)->select('shipment_color')->from($db->quoteName('#__fdshop_shipments'))->where('id='.$shipmentId));$shipmentColor=(string)$db->loadResult();
$packingBefore=$service->packingList($orderId);
$db->setQuery($db->getQuery(true)->update($db->quoteName('#__fdshop_shipments'))->set('shipment_color='.$db->quote('#ddee11'))->where('id='.$shipmentId))->execute();
$packingAfter=$service->packingList($orderId);
$db->setQuery($db->getQuery(true)->update($db->quoteName('#__fdshop_shipments'))->set('shipment_color='.$db->quote($shipmentColor))->where('id='.$shipmentId))->execute();
if(hash_equals(hash('sha256',$packingBefore['bytes']),hash('sha256',$packingAfter['bytes'])))throw new RuntimeException('Regenerated packing list ignored current shipment color.');

$db->setQuery($db->getQuery(true)->select('*')->from($db->quoteName('#__fdshop_order_items'))->where('order_id='.$orderId)->where('is_removed=0')->order('id ASC'),0,1);$source=$db->loadObject();
if(!$source)throw new RuntimeException('Test order has no item.');$created=[];
try{for($i=1;$i<=45;$i++){$copy=clone $source;unset($copy->id);$copy->product_name.=' Mehrseitentest '.$i;$db->insertObject('#__fdshop_order_items',$copy);$created[]=(int)$db->insertid();}$customerLarge=$service->customerDocument($orderId,false);$customerPages=preg_match_all('/\/Type\s*\/Page\b/',$customerLarge['bytes']);if($customerPages<2)throw new RuntimeException('Large customer confirmation is not multi-page.');$packing=$service->packingList($orderId);$pages=preg_match_all('/\/Type\s*\/Page\b/',$packing['bytes']);if($pages<2)throw new RuntimeException('Large packing list is not multi-page.');if(!str_starts_with($packing['bytes'],'%PDF-'))throw new RuntimeException('Packing list signature invalid.');echo 'document regression PASS customer_sha256='.hash('sha256',$first['bytes']).' customer_pages='.$customerPages.' packing_pages='.$pages."\n";}finally{if($created!==[]){$db->setQuery($db->getQuery(true)->delete($db->quoteName('#__fdshop_order_items'))->whereIn('id',$created))->execute();}}
