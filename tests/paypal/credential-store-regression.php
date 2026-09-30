<?php
define('_JEXEC', 1);
require_once '/workspace/fdshop/site/src/Service/CredentialResolverInterface.php';
require_once '/workspace/fdshop/site/src/Service/CredentialResolver.php';
require_once '/workspace/fdshop/site/src/Service/CredentialStore.php';

use FDShop\Component\FDShop\Site\Service\CredentialResolver;
use FDShop\Component\FDShop\Site\Service\CredentialStore;

$assert = static function (bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); };
$directory = sys_get_temp_dir() . '/fdshop-store-' . bin2hex(random_bytes(6));
mkdir($directory, 0700, true);
$file = $directory . '/fdshop-secrets.php';
$initial = ['paypal' => ['mode' => 'sandbox', 'sandbox' => ['client_id' => 'sandbox-id', 'client_secret' => 'sandbox-secret', 'webhook_id' => 'sandbox-hook'], 'live' => ['client_id' => 'live-id', 'client_secret' => 'live-secret', 'webhook_id' => 'live-hook']]];
file_put_contents($file, "<?php\nreturn " . var_export($initial, true) . ";\n");

try {
    foreach (['FDSHOP_PAYPAL_MODE','FDSHOP_PAYPAL_SANDBOX_CLIENT_ID','FDSHOP_PAYPAL_SANDBOX_CLIENT_SECRET','FDSHOP_PAYPAL_SANDBOX_WEBHOOK_ID','FDSHOP_PAYPAL_LIVE_CLIENT_ID','FDSHOP_PAYPAL_LIVE_CLIENT_SECRET','FDSHOP_PAYPAL_LIVE_WEBHOOK_ID'] as $name) putenv($name);
    (new CredentialStore(new CredentialResolver($file)))->update(['mode' => 'sandbox', 'sandbox_client_id' => 'changed-id', 'sandbox_client_secret' => '', 'sandbox_webhook_id' => 'changed-hook']);
    $data = include $file;
    $assert($data['paypal']['sandbox']['client_id'] === 'changed-id', 'Client-ID was not updated.');
    $assert($data['paypal']['sandbox']['client_secret'] === 'sandbox-secret', 'Blank secret did not preserve existing value.');
    $assert($data['paypal']['live'] === $initial['paypal']['live'], 'Sandbox update changed live credentials.');

    (new CredentialStore(new CredentialResolver($file)))->update(['mode' => 'sandbox', 'sandbox_client_secret' => 'replacement-secret']);
    $data = include $file;
    $assert($data['paypal']['sandbox']['client_secret'] === 'replacement-secret', 'New secret did not replace existing value.');

    putenv('FDSHOP_PAYPAL_SANDBOX_CLIENT_ID=environment-id');
    (new CredentialStore(new CredentialResolver($file)))->update(['mode' => 'sandbox', 'sandbox_client_id' => 'must-not-win']);
    $data = include $file;
    $assert($data['paypal']['sandbox']['client_id'] === 'changed-id', 'Environment-managed value was overwritten in file.');
    putenv('FDSHOP_PAYPAL_SANDBOX_CLIENT_ID');

    $data['paypal']['live']['webhook_id'] = '';
    file_put_contents($file, "<?php\nreturn " . var_export($data, true) . ";\n");
    $before = hash_file('sha256', $file);
    try { (new CredentialStore(new CredentialResolver($file)))->update(['mode' => 'live']); throw new RuntimeException('Incomplete live activation was accepted.'); }
    catch (RuntimeException $e) { $assert(str_contains($e->getMessage(), 'vollständiger'), 'Unexpected live validation error.'); }
    $assert(hash_file('sha256', $file) === $before, 'Rejected live activation changed original file.');

    (new CredentialStore(new CredentialResolver($file)))->update(['mode' => 'live', 'live_webhook_id' => 'new-live-hook']);
    $data = include $file;
    $assert($data['paypal']['mode'] === 'live' && $data['paypal']['live']['webhook_id'] === 'new-live-hook', 'Complete live activation failed.');
    $assert(!str_contains(file_get_contents($file), 'must-not-win'), 'Rejected value leaked into secret store.');

    $invalid = $directory . '/invalid.php';
    file_put_contents($invalid, '<?php return ["unexpected" => true];');
    $invalidBefore = hash_file('sha256', $invalid);
    try { (new CredentialStore(new CredentialResolver($invalid)))->update(['mode' => 'sandbox']); throw new RuntimeException('Invalid existing store was overwritten.'); }
    catch (RuntimeException $e) { $assert(str_contains($e->getMessage(), 'ungültig'), 'Unexpected invalid-store error.'); }
    $assert(hash_file('sha256', $invalid) === $invalidBefore, 'Invalid original store changed.');

    $beforeFailure = hash_file('sha256', $file);
    try {
        (new CredentialStore(new CredentialResolver($file), static fn (): bool => false))->update(['mode' => 'sandbox', 'sandbox_client_id' => 'must-not-persist']);
        throw new RuntimeException('Simulated atomic replace failure was accepted.');
    } catch (RuntimeException $e) {
        $assert(str_contains($e->getMessage(), 'atomar'), 'Unexpected simulated write failure.');
    }
    $assert(hash_file('sha256', $file) === $beforeFailure, 'Simulated write failure changed original file.');

    echo "Credential store regression: PASS\n";
} finally {
    foreach (glob($directory . '/*') ?: [] as $path) is_file($path) && unlink($path);
    rmdir($directory);
}
