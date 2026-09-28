<?php
declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_fdshop/src/Extension/FdshopComponent.php';
require_once JPATH_ADMINISTRATOR . '/components/com_fdshop/src/Service/WatchlistServiceInterface.php';
require_once JPATH_ADMINISTRATOR . '/components/com_fdshop/src/Service/WatchlistService.php';
require_once JPATH_ADMINISTRATOR . '/components/com_fdshop/src/Service/ProductServiceInterface.php';
require_once JPATH_ADMINISTRATOR . '/components/com_fdshop/src/Service/PackagingService.php';
require_once JPATH_ADMINISTRATOR . '/components/com_fdshop/src/Service/ProductService.php';
require_once JPATH_ROOT . '/components/com_fdshop/src/Helper/RouteHelper.php';

use FDShop\Component\FDShop\Administrator\Service\ProductServiceInterface;
use FDShop\Component\FDShop\Administrator\Service\WatchlistServiceInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Session\SessionInterface;

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$container = Factory::getContainer();
$container->alias(SessionInterface::class, 'session.web.site');
$app = $container->get(SiteApplication::class);
Factory::$application = $app;
$db = $container->get(DatabaseInterface::class);
$services = $app->bootComponent('com_fdshop')->getContainer();
$watchlist = $services->get(WatchlistServiceInterface::class);
$products = $services->get(ProductServiceInterface::class);
$productId = 900106;

$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};
$scalar = static function (string $sql) use ($db) { $db->setQuery($sql); return $db->loadResult(); };
$adminId = (int) $scalar("SELECT id FROM #__users ORDER BY id LIMIT 1");
$fakeId = 0;

try {
    $db->setQuery("UPDATE #__fdshop_products SET in_stock='Ausverkauft' WHERE id=" . $productId)->execute();
    $db->setQuery("DELETE FROM #__fdshop_product_watchlist WHERE product_id=" . $productId)->execute();
    $watchlist->activate($adminId, $productId);
    $watchlist->activate($adminId, $productId);
    $assert((int)$scalar("SELECT COUNT(*) FROM #__fdshop_product_watchlist WHERE product_id={$productId} AND user_id={$adminId} AND status='active'") === 1, 'Duplicate activation created more than one active watch.');
    $dashboard = array_values(array_filter($watchlist->dashboard(), static fn($row) => (int)$row->product_id === $productId));
    $assert(count($dashboard) === 1 && (int)$dashboard[0]->waiting_count === 1, 'Dashboard aggregation is incorrect.');
    $watchlist->remove($adminId + 99999, (int)$scalar("SELECT id FROM #__fdshop_product_watchlist WHERE product_id={$productId} AND user_id={$adminId}"));
    $assert((int)$scalar("SELECT COUNT(*) FROM #__fdshop_product_watchlist WHERE product_id={$productId} AND user_id={$adminId} AND status='active'") === 1, 'A foreign user removed the watch.');

    $db->setQuery("UPDATE #__fdshop_products_details SET stock_quantity=100,reserved_quantity=0,low_stock=10,is_in_stock=1 WHERE product_id=" . $productId)->execute();
    $products->recalculateStockStatus([$productId]);
    $assert((string)$scalar("SELECT in_stock FROM #__fdshop_products WHERE id=" . $productId) !== 'Ausverkauft', 'Stock recalculation did not make product available.');
    $assert((string)$scalar("SELECT status FROM #__fdshop_product_watchlist WHERE product_id={$productId} AND user_id={$adminId}") === 'active', 'Stock change triggered notification state automatically.');

    $first = $watchlist->notifyAvailable($productId);
    $assert($first['success'] === 1 && $first['failed'] === 0, 'Manual notification did not send exactly once: ' . json_encode($first));
    $second = $watchlist->notifyAvailable($productId);
    $assert($second['success'] === 0 && $second['failed'] === 0, 'Already notified recipient was sent again.');

    $db->setQuery("UPDATE #__fdshop_products SET in_stock='Ausverkauft' WHERE id=" . $productId)->execute();
    $watchlist->activate($adminId, $productId);
    $assert((int)$scalar("SELECT COUNT(*) FROM #__fdshop_product_watchlist WHERE product_id={$productId} AND user_id={$adminId} AND status='active' AND notified_at IS NULL") === 1, 'A notified watch could not be reactivated after a new sell-out.');
    $watchlist->remove($adminId, (int)$scalar("SELECT id FROM #__fdshop_product_watchlist WHERE product_id={$productId} AND user_id={$adminId}"));

    $row = (object)['name'=>'Retry Test','username'=>'fdshop-watch-retry','email'=>'invalid-address','password'=>'x','block'=>0,'sendEmail'=>0,'registerDate'=>Factory::getDate()->toSql(),'activation'=>'','params'=>'{}'];
    $db->insertObject('#__users', $row, 'id'); $fakeId = (int)$row->id;
    $db->setQuery("UPDATE #__fdshop_products SET in_stock='Ausverkauft' WHERE id=900109")->execute();
    $watchlist->activate($fakeId, 900109);
    $assert((int)$scalar("SELECT COUNT(*) FROM #__fdshop_user_buyer_group_map WHERE user_id={$fakeId}") === 0, 'A watch granted a buyer-group/F3 assignment.');
    $db->setQuery("DELETE FROM #__fdshop_product_watchlist WHERE product_id=900109 AND user_id=" . $fakeId)->execute();
    $db->setQuery("UPDATE #__fdshop_products SET in_stock='Verfügbar' WHERE id=900109")->execute();
    $db->setQuery("UPDATE #__fdshop_products SET in_stock='Ausverkauft' WHERE id=" . $productId)->execute();
    $watchlist->activate($fakeId, $productId);
    $db->setQuery("UPDATE #__fdshop_products SET in_stock='Verfügbar' WHERE id=" . $productId)->execute();
    $failed = $watchlist->notifyAvailable($productId);
    $assert($failed['success'] === 0 && $failed['failed'] === 1, 'Invalid recipient was not isolated as a retryable failure.');
    $assert((string)$scalar("SELECT status FROM #__fdshop_product_watchlist WHERE product_id={$productId} AND user_id={$fakeId}") === 'active', 'Failed recipient was not kept active for retry.');
    $db->setQuery("UPDATE #__users SET email='retry@example.test' WHERE id=" . $fakeId)->execute();
    $retry = $watchlist->notifyAvailable($productId);
    $assert($retry['success'] === 1 && $retry['failed'] === 0, 'Retry did not notify the corrected recipient.');
    echo "Watchlist service regression: PASS\n";
} finally {
    $db->setQuery("DELETE FROM #__fdshop_product_watchlist WHERE product_id=" . $productId)->execute();
    if ($fakeId > 0) $db->setQuery("DELETE FROM #__users WHERE id=" . $fakeId)->execute();
    $db->setQuery("UPDATE #__fdshop_products_details SET stock_quantity=0,reserved_quantity=0,low_stock=5,is_in_stock=0 WHERE product_id=" . $productId)->execute();
    $db->setQuery("UPDATE #__fdshop_products SET in_stock='Ausverkauft' WHERE id=" . $productId)->execute();
    $db->setQuery("UPDATE #__fdshop_products SET in_stock='Verfügbar' WHERE id=900109")->execute();
}
