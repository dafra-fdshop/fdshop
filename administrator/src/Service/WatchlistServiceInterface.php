<?php
namespace FDShop\Component\FDShop\Administrator\Service;
defined('_JEXEC') or die;
interface WatchlistServiceInterface
{
    public function activate(int $userId, int $productId): void;
    public function remove(int $userId, int $watchId): void;
    public function forUser(int $userId): array;
    public function forProduct(int $productId): array;
    public function dashboard(): array;
    public function notifyAvailable(int $productId): array;
}
