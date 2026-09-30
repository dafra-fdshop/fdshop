<?php
namespace FDShop\Component\FDShop\Administrator\Helper;
defined('_JEXEC') or die;

use DateTimeImmutable;
use DateTimeZone;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class DisplayDateHelper
{
    private static ?string $timezone = null;

    public static function timezone(): string
    {
        if (self::$timezone !== null) return self::$timezone;
        $value = '';
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $q = $db->getQuery(true)->select($db->quoteName('display_timezone'))->from($db->quoteName('#__fdshop_config'))->where('id=1');
            $db->setQuery($q); $value = trim((string) $db->loadResult());
        } catch (\Throwable) {}
        if (!in_array($value, DateTimeZone::listIdentifiers(), true)) $value = 'Europe/Berlin';
        return self::$timezone = $value;
    }

    public static function dateTime(?string $utc, string $format = 'd.m.Y H:i'): string
    {
        if (($utc = trim((string) $utc)) === '') return '';
        return self::utcInstant($utc)->setTimezone(new DateTimeZone(self::timezone()))->format($format);
    }

    public static function instantDate(?string $utc, string $format = 'd.m.Y'): string
    {
        return self::dateTime($utc, $format);
    }

    public static function calendarDate(?string $value, string $format = 'd.m.Y'): string
    {
        if (($value = trim((string) $value)) === '') return '';
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10), new DateTimeZone('UTC'));
        if (!$date) throw new \UnexpectedValueException('Ungültiges Kalenderdatum.');
        return $date->format($format);
    }

    public static function toDisplayInput(?string $utc): ?string
    {
        if (($utc = trim((string) $utc)) === '') return null;
        return self::dateTime($utc, 'Y-m-d H:i:s');
    }

    public static function fromDisplayInput(?string $local): ?string
    {
        if (($local = trim((string) $local)) === '') return null;
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $local, new DateTimeZone(self::timezone()));
        if (!$date || $date->format('Y-m-d H:i:s') !== $local) {
            throw new \UnexpectedValueException('Ungültiges Datum oder ungültige Uhrzeit.');
        }
        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    public static function setTimezoneForTesting(?string $timezone): void
    {
        if ($timezone !== null && !in_array($timezone, DateTimeZone::listIdentifiers(), true)) throw new \InvalidArgumentException('Ungültige Zeitzone.');
        self::$timezone = $timezone;
    }

    private static function utcInstant(string $value): DateTimeImmutable
    {
        $utc = new DateTimeZone('UTC');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, $utc);
        if ($date) return $date;
        return new DateTimeImmutable($value, $utc);
    }
}
