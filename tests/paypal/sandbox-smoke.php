<?php
declare(strict_types=1);

if (getenv('FDSHOP_TEST_EXTERNAL_CREDENTIALS') === '1') {
    $file = tempnam(sys_get_temp_dir(), 'fdshop-paypal-secrets-');
    if ($file === false) {
        throw new RuntimeException('Temporary secret file could not be created.');
    }
    $data = [
        'paypal' => [
            'mode' => 'sandbox',
            'sandbox' => [
                'client_id' => (string) getenv('FDSHOP_PAYPAL_SANDBOX_CLIENT_ID'),
                'client_secret' => (string) getenv('FDSHOP_PAYPAL_SANDBOX_CLIENT_SECRET'),
                'webhook_id' => (string) getenv('FDSHOP_PAYPAL_SANDBOX_WEBHOOK_ID'),
            ],
            'live' => ['client_id' => '', 'client_secret' => '', 'webhook_id' => ''],
        ],
    ];
    file_put_contents($file, "<?php\nreturn " . var_export($data, true) . ";\n");
    chmod($file, 0600);
    $command = 'env -u FDSHOP_TEST_EXTERNAL_CREDENTIALS -u FDSHOP_PAYPAL_MODE'
        . ' -u FDSHOP_PAYPAL_SANDBOX_CLIENT_ID -u FDSHOP_PAYPAL_SANDBOX_CLIENT_SECRET'
        . ' -u FDSHOP_PAYPAL_SANDBOX_WEBHOOK_ID FDSHOP_SECRET_FILE=' . escapeshellarg($file)
        . ' ' . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__);
    try {
        passthru($command, $status);
    } finally {
        unlink($file);
    }
    exit($status);
}

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_fdshop/src/Extension/FdshopComponent.php';
require_once JPATH_ROOT . '/components/com_fdshop/src/Service/CredentialResolverInterface.php';
require_once JPATH_ROOT . '/components/com_fdshop/src/Service/CredentialResolver.php';
require_once JPATH_ROOT . '/components/com_fdshop/src/Service/PayPalClientInterface.php';
require_once JPATH_ROOT . '/components/com_fdshop/src/Service/PayPalClient.php';

use FDShop\Component\FDShop\Site\Service\PayPalClientInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\Session\SessionInterface;

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$container = Factory::getContainer();
$container->alias(SessionInterface::class, 'session.web.site');
$application = $container->get(SiteApplication::class);
Factory::$application = $application;
$client = $application->bootComponent('com_fdshop')->getContainer()->get(PayPalClientInterface::class);
if ($client->mode() !== 'sandbox' || !$client->configured()) {
    throw new RuntimeException('Sandbox client is not configured.');
}
$order = $client->createOrder('fdshop-sandbox-smoke-' . bin2hex(random_bytes(8)), 1, 'EUR');
if (empty($order['id']) || ($order['status'] ?? '') !== 'CREATED') {
    throw new RuntimeException('Sandbox create-order response invalid.');
}
echo "PayPal sandbox authentication/create-order: PASS\n";
