<?php
declare(strict_types=1);
define('_JEXEC',1);define('JPATH_BASE','/var/www/html');require JPATH_BASE.'/includes/defines.php';require JPATH_BASE.'/includes/framework.php';
require_once JPATH_ADMINISTRATOR.'/components/com_fdshop/src/Extension/FdshopComponent.php';
require_once JPATH_ADMINISTRATOR.'/components/com_fdshop/src/Service/OrderDocumentService.php';
require_once JPATH_ADMINISTRATOR.'/components/com_fdshop/src/Service/InvoiceService.php';
use FDShop\Component\FDShop\Administrator\Service\InvoiceService;use Joomla\CMS\Application\SiteApplication;use Joomla\CMS\Factory;use Joomla\Session\SessionInterface;
$_SERVER['HTTP_HOST']='localhost';$_SERVER['REQUEST_URI']='/';$_SERVER['SCRIPT_NAME']='/index.php';$c=Factory::getContainer();$c->alias(SessionInterface::class,'session.web.site');$app=$c->get(SiteApplication::class);Factory::$application=$app;
$id=(int)($argv[1]??0);if($id<1)throw new RuntimeException('order id required');$doc=$app->bootComponent('com_fdshop')->getContainer()->get(InvoiceService::class)->issue($id);echo $doc['name']."\n";
