<?php

namespace FDShop\Component\FDShop\Site\Service;

defined('_JEXEC') or die;

interface CartContinuationServiceInterface
{
    public const COOKIE_NAME = 'fdshop_cart_continue';

    public function ensure(string $guestSessionId, string $intent, array $metadata, ?string $token = null): string;

    public function resolveAfterLogin(string $token, int $userId, array $userMetadata): array;

    public function getConflict(string $token, int $userId, array $userMetadata): ?array;

    public function choose(string $token, int $userId, string $choice, array $userMetadata): array;
}
