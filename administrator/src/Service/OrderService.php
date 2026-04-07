<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Service;

defined('_JEXEC') or die;

interface OrderServiceInterface
{
    public function addItem(int $orderId, int $productId, float $quantity = 1.0): int;

    public function removeItem(int $orderId, int $orderItemId): void;

    public function changeItemQuantity(int $orderId, int $orderItemId, float $quantity): void;

    public function recalculateGrandTotal(int $orderId): float;

    public function writeOrderHistory(
        int $orderId,
        string $eventType,
        string $eventTitle,
        ?string $eventText = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        bool $isSystemEvent = true
    ): int;

    public function writeOrdersHistory(
        int $orderId,
        string $historyType,
        $oldValue = null,
        $newValue = null,
        ?string $note = null
    ): int;
}