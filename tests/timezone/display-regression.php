<?php
declare(strict_types=1);
define('_JEXEC', 1);
require_once __DIR__ . '/../../administrator/src/Helper/DisplayDateHelper.php';

use FDShop\Component\FDShop\Administrator\Helper\DisplayDateHelper;

DisplayDateHelper::setTimezoneForTesting('Europe/Berlin');
$assert = static function (bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); };
$assert(DisplayDateHelper::dateTime('2026-09-30 05:26:00') === '30.09.2026 07:26', 'Summer order display is not CEST.');
$assert(DisplayDateHelper::dateTime('2026-01-15 05:26:00') === '15.01.2026 06:26', 'Winter order display is not CET.');
$assert(DisplayDateHelper::dateTime('2026-10-25 00:30:00', 'Y-m-d H:i P T') === '2026-10-25 02:30 +02:00 CEST', 'DST fall first side failed.');
$assert(DisplayDateHelper::dateTime('2026-10-25 01:30:00', 'Y-m-d H:i P T') === '2026-10-25 02:30 +01:00 CET', 'DST fall second side failed.');
$assert(DisplayDateHelper::dateTime('2026-03-29 00:30:00', 'Y-m-d H:i P T') === '2026-03-29 01:30 +01:00 CET', 'DST spring first side failed.');
$assert(DisplayDateHelper::dateTime('2026-03-29 01:30:00', 'Y-m-d H:i P T') === '2026-03-29 03:30 +02:00 CEST', 'DST spring second side failed.');
$assert(DisplayDateHelper::calendarDate('2026-03-29') === '29.03.2026', 'Calendar date shifted.');
$assert(DisplayDateHelper::toDisplayInput('2026-09-30 05:26:00') === '2026-09-30 07:26:00', 'Admin UTC to local input failed.');
$assert(DisplayDateHelper::fromDisplayInput('2026-09-30 07:26:00') === '2026-09-30 05:26:00', 'Admin local input to UTC failed.');
echo "FDShop display timezone regression: PASS\n";
