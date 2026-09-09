<?php

namespace FDShop\Component\FDShop\Site\Service;

defined('_JEXEC') or die;

interface CartServiceInterface
{
    public function getCart(int $userId, int $shipmentId = 0, int $paymentId = 0): array;

    public function addItem(int $userId, int $productId, float $quantity): array;

    public function updateQuantity(int $userId, int $cartId, float $quantity, int $shipmentId = 0, int $paymentId = 0): array;

    public function removeItem(int $userId, int $cartId, int $shipmentId = 0, int $paymentId = 0): array;

    public function validateShipment(int $shipmentId): int;

    public function validatePayment(int $paymentId): int;

    public function validateRemark(string $remark): string;
}
