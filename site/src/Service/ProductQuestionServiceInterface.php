<?php
namespace FDShop\Component\FDShop\Site\Service;
defined('_JEXEC') or die;
interface ProductQuestionServiceInterface
{
    public function send(int $userId, int $productId, array $data): void;
}
