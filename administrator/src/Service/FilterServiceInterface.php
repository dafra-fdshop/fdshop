<?php
namespace FDShop\Component\FDShop\Administrator\Service;
defined('_JEXEC') or die;
interface FilterServiceInterface
{
    public function getFilter(int $id): ?object;
    public function getFilters(): array;
    public function save(array $data): int;
}
