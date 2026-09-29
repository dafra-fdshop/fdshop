<?php
namespace FDShop\Component\FDShop\Site\Service;
defined('_JEXEC') or die;

interface PaymentClockInterface
{
    public function now(): \DateTimeImmutable;
    public function parseSql(string $value): \DateTimeImmutable;
    public function toSql(\DateTimeInterface $value): string;
    public function toAtom(\DateTimeInterface $value): string;
}
