<?php
namespace FDShop\Component\FDShop\Site\Service;
defined('_JEXEC') or die;

interface CheckoutServiceInterface
{
    public function createOrder(int $userId, string $sessionId, int $shipmentId, int $paymentId, string $couponCode, string $note, bool $termsAccepted, string $submissionId): array;
    public function getConfirmation(int $userId, string $orderNumber): ?array;
}
