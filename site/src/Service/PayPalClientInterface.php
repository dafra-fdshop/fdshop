<?php
namespace FDShop\Component\FDShop\Site\Service;
defined('_JEXEC') or die;

interface PayPalClientInterface
{
    public function configured(): bool;
    public function mode(): string;
    public function clientId(): string;
    public function createOrder(string $requestId, int $amountMinor, string $currency): array;
    public function captureOrder(string $providerOrderId, string $requestId): array;
    public function verifyWebhook(array $headers, string $body): bool;
}
