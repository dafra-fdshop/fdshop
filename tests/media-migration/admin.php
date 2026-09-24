#!/usr/bin/env php
<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/tools/media-migration/import.php';

[, , $service] = bootstrapJoomla();
$paths = $service->getPaths();
$skus = ['FD1300', 'FD1313', 'FD1392', 'FD1442', 'FD1493'];

foreach (glob($paths['staging'] . '/*') ?: [] as $path) {
    if (is_file($path) || is_link($path)) {
        unlink($path);
    }
}
@unlink($paths['state']);

if (in_array('--full-dry-run', $argv, true)) {
    $masters = glob('/workspace/fdshop-media-masters/*.png') ?: [];
    foreach ($masters as $source) {
        if (!copy($source, $paths['staging'] . '/' . basename($source))) {
            throw new RuntimeException('Master konnte nicht für den Dry-Run bereitgestellt werden: ' . $source);
        }
    }
    $started = microtime(true);
    $analysis = $service->analyse();
    $runtime = microtime(true) - $started;
    if (count($analysis['items']) !== count($masters) || count($masters) < 500) {
        throw new RuntimeException('Der vollständige Dry-Run hat nicht alle Master erfasst.');
    }
    if ($service->clearStaging() !== count($masters)) {
        throw new RuntimeException('Der vollständige Dry-Run konnte sein Staging nicht bereinigen.');
    }
    printf("Admin full dry-run: PASS (%d masters, %.3f seconds)\n", count($masters), $runtime);
    exit(0);
}

foreach ($skus as $sku) {
    $source = '/workspace/fdshop-media-masters/' . strtolower($sku) . '.png';
    $target = $paths['staging'] . '/' . strtolower($sku) . '.png';
    if (!is_file($source) || !copy($source, $target)) {
        throw new RuntimeException('Testmaster fehlt: ' . $source);
    }
}

$analysis = $service->analyse();
$ready = array_values(array_filter($analysis['items'], static fn(array $row): bool => $row['status'] === 'READY'));
if (count($ready) !== count($skus)) {
    throw new RuntimeException('Erwartet wurden fünf READY-Master: ' . json_encode($analysis['counts']));
}

$batch = $service->createBatch($skus, 0);
$result = $service->processBatch($batch['id'], 0);
if (!$result['complete'] || $result['processed'] !== 5 || ($result['counts']['IMPORTED'] ?? 0) !== 5) {
    throw new RuntimeException('Admin-Batch wurde nicht vollständig importiert: ' . json_encode($result));
}

$second = $service->analyse();
if (($second['counts']['ALREADY_IMPORTED'] ?? 0) !== 5 || ($second['counts']['READY'] ?? 0) !== 0) {
    throw new RuntimeException('Idempotenzstatus unerwartet: ' . json_encode($second['counts']));
}

$removed = $service->clearStaging();
if ($removed !== 5 || !is_file($paths['state'])) {
    throw new RuntimeException('Staging-Cleanup oder Import-State unerwartet.');
}

@unlink($paths['state']);
foreach (glob($paths['batches'] . '/*') ?: [] as $path) {
    if (is_file($path)) {
        @unlink($path);
    }
}

echo "Admin master image import: PASS (5 imported, idempotent, staging cleaned)\n";
