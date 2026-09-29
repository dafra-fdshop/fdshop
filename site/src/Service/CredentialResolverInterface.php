<?php
namespace FDShop\Component\FDShop\Site\Service;
defined('_JEXEC') or die;

interface CredentialResolverInterface
{
    public function paypalMode(): string;
    public function paypal(string $name): string;
    public function paypalConfigured(string $name): bool;
}
