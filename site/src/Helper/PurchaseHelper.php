<?php

namespace FDShop\Component\FDShop\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class PurchaseHelper
{
    public static function isShopEnabled(): bool
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)->select($db->quoteName('katalog_active'))
            ->from($db->quoteName('#__fdshop_config'))->where($db->quoteName('id') . ' = 1');
        $db->setQuery($query);
        return (int) $db->loadResult() !== 1;
    }

    public static function data(object $item, string $unitVariant = 'piece'): array
    {
        $minimum = (float) $item->min_order_qty > 0 ? (float) $item->min_order_qty : 1.0;
        $step = (float) $item->step_order_qty > 0 ? (float) $item->step_order_qty : 1.0;
        $maximum = (float) $item->max_order_qty > 0 ? (float) $item->max_order_qty : 0.0;

        if ($unitVariant === 'package') {
            $minimum = 1.0;
            $step = 1.0;
            $available = max(0, (float) ($item->stock_quantity ?? 0) - (float) ($item->reserved_quantity ?? 0));
            $maximum = floor($available / max(1, (int) ($item->package['unit_quantity'] ?? 1)));
        }
        return compact('item', 'minimum', 'step', 'maximum', 'unitVariant');
    }
}
