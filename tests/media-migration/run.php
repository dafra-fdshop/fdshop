<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/tools/media-migration/import.php';

$failures = 0;
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); $failures++; }
};

$assert(normalizeSkuFromFilename('fd1000.png') === 'FD1000', 'lowercase filename');
$assert(normalizeSkuFromFilename('FD9999.png') === 'FD9999', 'uppercase filename');
foreach (['fd100.png','fd10000.png','fd10A0.png','produkt.png','fd1000-final.png'] as $name) {
    $assert(normalizeSkuFromFilename($name) === null, 'invalid filename ' . $name);
}
$assert(normalizeSkuFilter('fd1234') === 'FD1234', 'SKU filter normalization');
$root = sys_get_temp_dir() . '/fdshop-media-test-' . bin2hex(random_bytes(4));
mkdir($root); file_put_contents($root . '/fd1000.png', 'test');
$assert(safeMasterPath($root, $root . '/fd1000.png') !== null, 'safe in-root file');
$assert(safeMasterPath($root, $root . '/../outside.png') === null, 'traversal/outside blocked');
if (function_exists('symlink')) { symlink('/etc/hosts', $root . '/fd1001.png'); $assert(safeMasterPath($root, $root . '/fd1001.png') === null, 'external symlink blocked'); @unlink($root . '/fd1001.png'); }
@unlink($root . '/fd1000.png'); @rmdir($root);
if ($failures > 0) { exit(1); }
echo "Media migration unit tests: PASS\n";
