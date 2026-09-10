<?php

namespace FDShop\Component\FDShop\Site\Service;

defined('_JEXEC') or die;

interface CartServiceInterface
{
    public function getCart(int $userId, string $sessionId, int $shipmentId = 0, int $paymentId = 0, string $couponCode = ''): array;

    public function addItem(int $userId, string $sessionId, int $productId, float $quantity): array;

    public function updateQuantity(int $userId, string $sessionId, int $cartId, float $quantity, int $shipmentId = 0, int $paymentId = 0): array;

    public function removeItem(int $userId, string $sessionId, int $cartId, int $shipmentId = 0, int $paymentId = 0): array;

    public function validateShipment(int $shipmentId): int;

    public function validatePayment(int $paymentId): int;

    public function validateCoupon(int $userId, string $sessionId, string $couponCode, int $shipmentId = 0, int $paymentId = 0): array;

}
