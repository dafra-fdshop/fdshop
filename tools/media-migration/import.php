#!/usr/bin/env php
<?php
declare(strict_types=1);

const TOOL_VERSION = '1.0';
const STATUS_READY = 'READY';
const STATUS_IMPORTED = 'IMPORTED';
const STATUS_ALREADY_IMPORTED = 'ALREADY_IMPORTED';
const STATUS_PRODUCT_NOT_FOUND = 'PRODUCT_NOT_FOUND';
const STATUS_INVALID_MASTER_FILENAME = 'INVALID_MASTER_FILENAME';
const STATUS_UNREADABLE_MASTER = 'UNREADABLE_MASTER';
const STATUS_DUPLICATE_MASTER_SKU = 'DUPLICATE_MASTER_SKU';
const STATUS_DUPLICATE_PRODUCT_SKU = 'DUPLICATE_PRODUCT_SKU';
const STATUS_EXISTING_MEDIA_REVIEW = 'EXISTING_MEDIA_REVIEW';
const STATUS_MASTER_CHANGED_REVIEW = 'MASTER_CHANGED_REVIEW';
const STATUS_PROCESSING_ERROR = 'PROCESSING_ERROR';
const STATUS_IMPORT_STATE_MISMATCH = 'IMPORT_STATE_MISMATCH';

function normalizeSkuFromFilename(string $filename): ?string
{
    return preg_match('/^fd([0-9]{4})\.png$/i', $filename, $match) === 1 ? 'FD' . $match[1] : null;
}

function normalizeSkuFilter(string $sku): ?string
{
    $sku = strtoupper(trim($sku));
    return preg_match('/^FD[0-9]{4}$/', $sku) === 1 ? $sku : null;
}

function safeMasterPath(string $base, string $path): ?string
{
    $baseReal = realpath($base);
    $pathReal = realpath($path);
    if ($baseReal === false || $pathReal === false || is_link($path) || !is_file($pathReal)) {
        return null;
    }
    $prefix = rtrim($baseReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    return str_starts_with($pathReal, $prefix) ? $pathReal : null;
}

function inventoryMasters(string $masterDir): array
{
    $base = realpath($masterDir);
    if ($base === false || !is_dir($base)) {
        throw new RuntimeException('Master directory does not exist: ' . $masterDir);
    }
    $rows = [];
    foreach (new DirectoryIterator($base) as $item) {
        if ($item->isDot() || $item->isDir()) {
            continue;
        }
        $filename = $item->getFilename();
        $sku = normalizeSkuFromFilename($filename);
        $safePath = safeMasterPath($base, $item->getPathname());
        $readable = false;
        $width = $height = 0;
        $alpha = false;
        $note = '';
        if ($safePath !== null && is_readable($safePath)) {
            $info = @getimagesize($safePath);
            $readable = $info !== false && ($info['mime'] ?? '') === 'image/png';
            if ($readable) {
                [$width, $height] = [(int) $info[0], (int) $info[1]];
                $image = @imagecreatefrompng($safePath);
                if ($image !== false) {
                    $alpha = imagecolortransparent($image) >= 0;
                    if (!$alpha && $width > 0 && $height > 0) {
                        foreach ([[0,0],[$width-1,0],[0,$height-1],[$width-1,$height-1]] as [$x,$y]) {
                            if (((imagecolorat($image, $x, $y) >> 24) & 0x7f) > 0) { $alpha = true; break; }
                        }
                    }
                }
            } else {
                $note = 'File content is not a readable PNG.';
            }
        } else {
            $note = 'Unsafe, linked, or unreadable source path.';
        }
        if ($sku === null) { $note = 'Filename does not match fdXXXX.png.'; }
        $rows[] = [
            'master_filename' => $filename, 'normalized_sku' => $sku ?? '',
            'file_size' => $safePath !== null ? (string) filesize($safePath) : '0',
            'width' => (string) $width, 'height' => (string) $height,
            'has_alpha' => $alpha ? '1' : '0',
            'sha256' => $safePath !== null ? hash_file('sha256', $safePath) : '',
            'readable' => $readable ? '1' : '0', 'filename_valid' => $sku !== null ? '1' : '0',
            'source_path' => $safePath ?? '', 'note' => $note,
        ];
    }
    usort($rows, static fn(array $a, array $b): int => [$a['normalized_sku'], $a['master_filename']] <=> [$b['normalized_sku'], $b['master_filename']]);
    $counts = [];
    foreach ($rows as $row) { if ($row['normalized_sku'] !== '') { $counts[$row['normalized_sku']] = ($counts[$row['normalized_sku']] ?? 0) + 1; } }
    foreach ($rows as &$row) { $row['duplicate_sku'] = ($row['normalized_sku'] !== '' && $counts[$row['normalized_sku']] > 1) ? '1' : '0'; }
    unset($row);
    return $rows;
}

function masterFingerprint(array $inventory): string
{
    $parts = array_map(static fn(array $r): string => $r['master_filename'] . "\0" . $r['file_size'] . "\0" . $r['sha256'], $inventory);
    return hash('sha256', implode("\n", $parts));
}

function writeCsv(string $path, array $rows, array $fields): void
{
    $handle = fopen($path, 'wb');
    if ($handle === false) { throw new RuntimeException('Cannot write report: ' . $path); }
    fputcsv($handle, $fields, ',', '"', '');
    foreach ($rows as $row) { fputcsv($handle, array_map(static fn(string $field): string => (string) ($row[$field] ?? ''), $fields), ',', '"', ''); }
    fclose($handle);
}

function loadState(string $path): array
{
    if (!is_file($path)) { return ['version' => 1, 'imports' => []]; }
    $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    return is_array($data) && isset($data['imports']) && is_array($data['imports']) ? $data : throw new RuntimeException('Invalid import state.');
}

function writeStateAtomic(string $path, array $state): void
{
    $tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
    file_put_contents($tmp, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n", LOCK_EX);
    if (!rename($tmp, $path)) { @unlink($tmp); throw new RuntimeException('Could not atomically replace import state.'); }
}

function bootstrapJoomla(): array
{
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? '80';
    $_SERVER['REQUEST_URI'] = '/';
    define('_JEXEC', 1);
    define('JPATH_BASE', '/var/www/html');
    require_once JPATH_BASE . '/includes/defines.php';
    require_once JPATH_BASE . '/includes/framework.php';
    JLoader::registerNamespace(
        'FDShop\\Component\\FDShop\\Administrator',
        JPATH_ADMINISTRATOR . '/components/com_fdshop/src'
    );
    $container = Joomla\CMS\Factory::getContainer();
    $container->alias('session', 'session.cli')
        ->alias(Joomla\CMS\Session\Session::class, 'session.cli')
        ->alias(Joomla\Session\Session::class, 'session.cli')
        ->alias(Joomla\Session\SessionInterface::class, 'session.cli');
    $app = $container->get(Joomla\Console\Application::class);
    Joomla\CMS\Factory::$application = $app;
    $component = $app->bootComponent('com_fdshop');
    return [
        $container->get(Joomla\Database\DatabaseInterface::class),
        $component->getContainer()->get(FDShop\Component\FDShop\Administrator\Service\ProductServiceInterface::class),
        $component->getContainer()->get(FDShop\Component\FDShop\Administrator\Service\MasterImageImportService::class),
    ];
}

function mediaExistsAndMatches(object $db, array $entry): bool
{
    $query = $db->getQuery(true)->select('*')->from($db->quoteName('#__fdshop_media'))
        ->where($db->quoteName('id') . ' = ' . (int) ($entry['media_id'] ?? 0))
        ->where($db->quoteName('product_id') . ' = ' . (int) ($entry['product_id'] ?? 0))
        ->where($db->quoteName('media_type') . ' = ' . $db->quote('image'));
    $db->setQuery($query); $media = $db->loadAssoc();
    if (!$media) { return false; }
    foreach (['path_standard','path_small','path_mobile','path_invoice'] as $field) {
        if (($media[$field] ?? '') !== ($entry[$field] ?? '') || !is_file(JPATH_ROOT . $media[$field])) { return false; }
    }
    return true;
}

function usage(): void
{
    echo "FDShop Master Media Migration Importer " . TOOL_VERSION . "\n"
        . "Usage: php import.php [--master-dir=PATH] [--report-dir=PATH] [--sku=FD1234,FD1235] [--execute] [--all]\n"
        . "Default is a read-only full dry-run. Execute requires --sku or --all.\n";
}

function main(array $argv): int
{
    $options = getopt('', ['help','master-dir:','report-dir:','sku:','execute','all']);
    if (isset($options['help'])) { usage(); return 0; }
    $masterDir = (string) ($options['master-dir'] ?? '/workspace/fdshop-media-masters');
    $reportDir = (string) ($options['report-dir'] ?? '/workspace/artifacts/media-migration');
    $execute = isset($options['execute']); $all = isset($options['all']);
    $filters = [];
    foreach (explode(',', (string) ($options['sku'] ?? '')) as $raw) {
        if (trim($raw) === '') { continue; }
        $sku = normalizeSkuFilter($raw);
        if ($sku === null) { throw new InvalidArgumentException('Invalid SKU filter: ' . $raw); }
        $filters[$sku] = true;
    }
    if ($execute && !$all && $filters === []) { throw new InvalidArgumentException('--execute requires --sku or explicit --all.'); }
    if ($all && !$execute) { throw new InvalidArgumentException('--all is only valid with --execute.'); }
    if (!is_dir($reportDir) && !mkdir($reportDir, 0775, true)) { throw new RuntimeException('Cannot create report directory.'); }

    $started = microtime(true); $inventory = inventoryMasters($masterDir); $fingerprint = masterFingerprint($inventory);
    [$db, $productService, $importService] = bootstrapJoomla();
    $db->setQuery($db->getQuery(true)->select(['image_size_default','image_quality_default','image_size_small','image_quality_small','image_size_mobile','image_quality_mobile'])->from($db->quoteName('#__fdshop_config'))->where('id=1'));
    $config = $db->loadAssoc() ?: [];
    $statePath = $reportDir . '/master_media_import_state.json';
    $analysis = $importService->analyse($masterDir, $statePath); $rows = [];
    $inventoryByName=[]; foreach($inventory as $item){$inventoryByName[$item['master_filename']]=$item;}
    foreach ($analysis['items'] as $common) {
        $sku=$common['sku']; if($filters!==[]&&($sku===''||!isset($filters[$sku])))continue;
        $status=$common['status']; $result=[];
        if($execute&&$status===STATUS_READY){$result=$importService->importOne($sku,$common['sha256'],0,$masterDir,$statePath);$status=$result['status'];}
        $item=$inventoryByName[$common['filename']]??['master_filename'=>$common['filename'],'sha256'=>$common['sha256'],'note'=>$common['note']];
        $media=[]; if(isset($result['media_id'])){$db->setQuery($db->getQuery(true)->select('*')->from($db->quoteName('#__fdshop_media'))->where('id='.(int)$result['media_id']));$media=$db->loadAssoc()?:[];}
        $rows[]=$item+['product_id'=>(string)$common['product_id'],'product_found'=>$common['product_id']>0?'1':'0','existing_media_count'=>(string)$common['existing_media_count'],'planned_action'=>$common['status']===STATUS_READY?'IMPORT':'SKIP','status'=>$status,'media_id'=>(string)($result['media_id']??''),'standard_path'=>(string)($media['path_standard']??''),'small_path'=>(string)($media['path_small']??''),'mobile_path'=>(string)($media['path_mobile']??''),'invoice_path'=>(string)($media['path_invoice']??''),'result_note'=>(string)($result['message']??$common['message'])];
        printf("%-8s %s\n",$sku?:$common['filename'],$status);
    }
    $inventoryFields=['master_filename','normalized_sku','file_size','width','height','has_alpha','sha256','readable','filename_valid','note'];
    $resultFields=['master_filename','master_sha256','normalized_sku','product_id','product_found','existing_media_count','planned_action','status','media_id','standard_path','small_path','mobile_path','invoice_path','result_note'];
    foreach ($rows as &$row) { $row['master_sha256']=$row['sha256']; } unset($row);
    writeCsv($reportDir.'/master_media_import_inventory.csv',$inventory,$inventoryFields);
    writeCsv($reportDir.'/master_media_import_plan.csv',$rows,$resultFields);
    writeCsv($reportDir.'/master_media_import_results.csv',$rows,$resultFields);
    $statuses=[]; foreach($rows as $row){$statuses[$row['status']]=($statuses[$row['status']]??0)+1;} ksort($statuses);
    $summary="FDShop Master Media Migration Importer\nTool version: ".TOOL_VERSION."\nMode: ".($execute?'EXECUTE':'DRY-RUN')."\nMaster fingerprint: $fingerprint\nMaster files: ".count($inventory)."\nMedia configuration: ".json_encode($config)."\nStatuses: ".json_encode($statuses)."\nRuntime seconds: ".number_format(microtime(true)-$started,3,'.','')."\n";
    file_put_contents($reportDir.'/master_media_import_summary.txt',$summary); echo $summary;
    return isset($statuses[STATUS_PROCESSING_ERROR]) ? 2 : 0;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try { exit(main($argv)); } catch (Throwable $e) { fwrite(STDERR, $e::class . ': ' . $e->getMessage() . "\n"); exit(1); }
}
