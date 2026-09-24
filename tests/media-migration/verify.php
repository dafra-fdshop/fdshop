<?php
declare(strict_types=1);
$statePath = $argv[1] ?? '/workspace/media-migration-artifacts/testset/master_media_import_state.json';
$state = json_decode((string) file_get_contents($statePath), true, flags: JSON_THROW_ON_ERROR);
foreach ($state['imports'] as $sku => $entry) {
    echo $sku . ' master=' . $entry['master_filename'] . ' sha256=' . $entry['master_sha256'] . PHP_EOL;
    foreach (['path_standard','path_small','path_mobile','path_invoice'] as $field) {
        $path = '/var/www/html' . $entry[$field];
        $info = getimagesize($path);
        if ($info === false || filesize($path) < 1) { throw new RuntimeException("Invalid derivative $sku $field"); }
        echo '  ' . $field . '=' . $entry[$field] . ' ' . $info[0] . 'x' . $info[1] . ' ' . filesize($path) . 'B ' . $info['mime'] . PHP_EOL;
    }
}
