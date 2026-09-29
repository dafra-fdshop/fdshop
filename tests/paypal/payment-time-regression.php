<?php
declare(strict_types=1);
define('_JEXEC', 1);
require_once __DIR__.'/../../site/src/Service/PaymentClockInterface.php';
require_once __DIR__.'/../../site/src/Service/PaymentUtcClock.php';

use FDShop\Component\FDShop\Site\Service\PaymentUtcClock;

$clock = new PaymentUtcClock();
$utc = new DateTimeZone('UTC');
$berlin = new DateTimeZone('Europe/Berlin');
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$cases = [
    'summer' => '2026-07-15 10:00:00',
    'winter' => '2026-01-15 10:00:00',
    'summer-to-winter' => '2026-10-25 00:55:00',
    'winter-to-summer' => '2026-03-29 00:55:00',
];

foreach ($cases as $label => $startValue) {
    $start = new DateTimeImmutable($startValue, $utc);
    $expires = $start->modify('+600 seconds');
    $roundTrip = $clock->parseSql($clock->toSql($expires));
    $assert($roundTrip->getTimestamp() - $start->getTimestamp() === 600, $label.': UTC lifetime changed.');
    $assert(str_ends_with($clock->toAtom($roundTrip), '+00:00'), $label.': serialized timestamp has no UTC offset.');
}

$fallStart = new DateTimeImmutable($cases['summer-to-winter'], $utc);
$fallEnd = $fallStart->modify('+600 seconds');
$assert($fallStart->setTimezone($berlin)->format('Y-m-d H:i:s P') === '2026-10-25 02:55:00 +02:00', 'Fall transition start is unexpected.');
$assert($fallEnd->setTimezone($berlin)->format('Y-m-d H:i:s P') === '2026-10-25 02:05:00 +01:00', 'Fall transition end is unexpected.');

$springStart = new DateTimeImmutable($cases['winter-to-summer'], $utc);
$springEnd = $springStart->modify('+600 seconds');
$assert($springStart->setTimezone($berlin)->format('Y-m-d H:i:s P') === '2026-03-29 01:55:00 +01:00', 'Spring transition start is unexpected.');
$assert($springEnd->setTimezone($berlin)->format('Y-m-d H:i:s P') === '2026-03-29 03:05:00 +02:00', 'Spring transition end is unexpected.');

echo "PayPal payment UTC/DST regression: PASS\n";
