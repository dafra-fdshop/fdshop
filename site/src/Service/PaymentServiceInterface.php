<?php
namespace FDShop\Component\FDShop\Site\Service;
defined('_JEXEC') or die;
interface PaymentServiceInterface
{
    public function start(int $userId,string $cartSessionId,int $shipmentId,int $paymentId,string $couponCode,string $note,bool $termsAccepted,string $submissionId):array;
    public function capture(int $userId,string $sessionToken,string $providerOrderId):array;
    public function cleanupExpired():int;
    public function webhook(array $headers,string $body):void;
    public function publicConfig():array;
    public function abandonActiveForUser(int $userId): int;
}
