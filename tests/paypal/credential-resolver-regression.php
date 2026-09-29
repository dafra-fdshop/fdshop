<?php
declare(strict_types=1);
define('_JEXEC', 1);
require_once '/workspace/fdshop/site/src/Service/CredentialResolverInterface.php';
require_once '/workspace/fdshop/site/src/Service/CredentialResolver.php';

use FDShop\Component\FDShop\Site\Service\CredentialResolver;

$names = [
    'FDSHOP_PAYPAL_MODE',
    'FDSHOP_PAYPAL_SANDBOX_CLIENT_ID',
    'FDSHOP_PAYPAL_SANDBOX_CLIENT_SECRET',
    'FDSHOP_PAYPAL_SANDBOX_WEBHOOK_ID',
    'FDSHOP_PAYPAL_LIVE_CLIENT_ID',
    'FDSHOP_PAYPAL_LIVE_CLIENT_SECRET',
    'FDSHOP_PAYPAL_LIVE_WEBHOOK_ID',
    'FDSHOP_SECRET_FILE',
];
$original = array_combine($names, array_map(static fn (string $name): string|false => getenv($name), $names));
$directory = sys_get_temp_dir() . '/fdshop-credential-test-' . bin2hex(random_bytes(6));
mkdir($directory, 0700, true);
$valid = $directory . '/valid.php';
$invalid = $directory . '/invalid.php';
$incomplete = $directory . '/incomplete.php';

$write = static function (string $file, string $body): void {
    if (file_put_contents($file, $body) === false) {
        throw new RuntimeException('Test secret file could not be created.');
    }
    chmod($file, 0600);
};
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

try {
    $write($valid, <<<'PHP'
<?php
return ['paypal' => ['mode' => 'sandbox', 'sandbox' => ['client_id' => 'file-sandbox-id', 'client_secret' => 'file-sandbox-secret', 'webhook_id' => 'file-sandbox-webhook'], 'live' => ['client_id' => 'file-live-id', 'client_secret' => 'file-live-secret', 'webhook_id' => 'file-live-webhook']]];
PHP);
    $write($invalid, "<?php echo 'MUST_NOT_LEAK'; trigger_error('MUST_NOT_LEAK'); return ['paypal' => 'invalid'];\n");
    $write($incomplete, "<?php return ['paypal' => ['mode' => 'sandbox', 'sandbox' => ['client_id' => 'only-id']]];\n");

    foreach ($names as $name) {
        putenv($name);
    }
    putenv('FDSHOP_PAYPAL_MODE=sandbox');
    putenv('FDSHOP_PAYPAL_SANDBOX_CLIENT_ID=environment-id');
    putenv('FDSHOP_PAYPAL_SANDBOX_CLIENT_SECRET=environment-secret');
    $resolver = new CredentialResolver($valid);
    $assert($resolver->paypal('client_id') === 'environment-id', 'Environment client ID did not win.');
    $assert($resolver->paypal('client_secret') === 'environment-secret', 'Environment secret did not win.');
    $assert($resolver->paypal('webhook_id') === 'file-sandbox-webhook', 'File fallback was not used.');

    putenv('FDSHOP_PAYPAL_SANDBOX_CLIENT_ID');
    putenv('FDSHOP_PAYPAL_SANDBOX_CLIENT_SECRET');
    $resolver = new CredentialResolver($valid);
    $assert($resolver->paypalMode() === 'sandbox' && $resolver->paypalConfigured('client_id') && $resolver->paypalConfigured('client_secret'), 'Sandbox file credentials were not detected.');
    putenv('FDSHOP_PAYPAL_MODE=live');
    $resolver = new CredentialResolver($valid);
    $assert($resolver->paypal('client_id') === 'file-live-id' && $resolver->paypal('webhook_id') === 'file-live-webhook', 'Live credentials were not separated.');

    putenv('FDSHOP_PAYPAL_MODE');
    $assert((new CredentialResolver($directory . '/missing.php'))->paypal('client_id') === '', 'Missing file was not handled.');
    ob_start();
    $invalidResolver = new CredentialResolver($invalid);
    $invalidValue = $invalidResolver->paypal('client_id');
    $leakedOutput = ob_get_clean();
    $assert($invalidValue === '' && $leakedOutput === '', 'Invalid file leaked output or values.');
    $incompleteResolver = new CredentialResolver($incomplete);
    $assert($incompleteResolver->paypalConfigured('client_id') && !$incompleteResolver->paypalConfigured('client_secret'), 'Incomplete file status was not handled.');

    echo "Credential resolver regression: PASS\n";
} finally {
    foreach ($original as $name => $value) {
        putenv($value === false ? $name : $name . '=' . $value);
    }
    foreach ([$valid, $invalid, $incomplete] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    if (is_dir($directory)) {
        rmdir($directory);
    }
}
