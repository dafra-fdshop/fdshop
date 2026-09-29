<?php
namespace FDShop\Component\FDShop\Site\Service;
defined('_JEXEC') or die;

final class PaymentUtcClock implements PaymentClockInterface
{
    private readonly \DateTimeZone $utc;

    public function __construct()
    {
        $this->utc = new \DateTimeZone('UTC');
    }

    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', $this->utc);
    }

    public function parseSql(string $value): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, $this->utc);
        $errors = \DateTimeImmutable::getLastErrors();

        if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new \UnexpectedValueException('Ungültiger technischer Payment-Zeitpunkt.');
        }

        return $date;
    }

    public function toSql(\DateTimeInterface $value): string
    {
        return \DateTimeImmutable::createFromInterface($value)->setTimezone($this->utc)->format('Y-m-d H:i:s');
    }

    public function toAtom(\DateTimeInterface $value): string
    {
        return \DateTimeImmutable::createFromInterface($value)->setTimezone($this->utc)->format(DATE_ATOM);
    }
}
