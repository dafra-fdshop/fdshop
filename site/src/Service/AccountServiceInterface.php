<?php
namespace FDShop\Component\FDShop\Site\Service;
defined('_JEXEC') or die;
interface AccountServiceInterface
{
    public function dashboard(int $userId, int $page = 1, ?int $orderId = null): array;
    public function saveProfile(int $userId, array $profile): void;
    public function saveCredentials(int $userId, ?string $username, ?string $password): void;
    public function requestEmailChange(int $userId, string $email): void;
    public function verifyEmail(string $token): void;
    public function requestShipment(int $userId, int $orderId, int $shipmentId): void;
    public function declareWithdrawal(int $userId, int $orderId): void;
    public function submitF3Documents(int $userId, array $files): void;
    public function decideWithdrawal(int $orderId, bool $accept, string $reason, int $adminId): void;
    public function resolveShipmentRequest(int $orderId, int $adminId): void;
}
