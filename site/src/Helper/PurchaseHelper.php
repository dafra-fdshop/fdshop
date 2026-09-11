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

    public static function data(object $item): array
    {
        $minimum = (float) $item->min_order_qty > 0 ? (float) $item->min_order_qty : 1.0;
        $step = (float) $item->step_order_qty > 0 ? (float) $item->step_order_qty : 1.0;
        $maximum = (float) $item->max_order_qty > 0 ? (float) $item->max_order_qty : 0.0;

        return compact('item', 'minimum', 'step', 'maximum');
    }
}
