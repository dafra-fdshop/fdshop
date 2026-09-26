<?php
declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_fdshop/src/Extension/FdshopComponent.php';
require_once JPATH_ADMINISTRATOR . '/components/com_fdshop/src/Service/OrderDocumentService.php';

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';

use FDShop\Component\FDShop\Administrator\Service\OrderDocumentService;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\Session\SessionInterface;

$container = Factory::getContainer();
$container->alias(SessionInterface::class, 'session.web.site');
$app = $container->get(SiteApplication::class);
Factory::$application = $app;
$service = $app->bootComponent('com_fdshop')->getContainer()->get(OrderDocumentService::class);
$orderId = (int)($argv[1] ?? 0);
$output = rtrim((string)($argv[2] ?? '/tmp'), '/');
if ($orderId < 1) throw new RuntimeException('Order id required.');
foreach ([$service->customerDocument($orderId, true), $service->packingList($orderId)] as $document) {
    $path = $output . '/' . $document['name'];
    if (file_put_contents($path, $document['bytes']) === false) throw new RuntimeException('Cannot write '.$path);
    if (!str_starts_with($document['bytes'], '%PDF-')) throw new RuntimeException('Invalid PDF signature: '.$path);
    echo $path.' '.strlen($document['bytes'])." bytes\n";
}
