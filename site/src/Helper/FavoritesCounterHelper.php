<?php

namespace FDShop\Component\FDShop\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

final class FavoritesCounterHelper
{
    private static ?array $state = null;

    public static function get(): array
    {
        if (self::$state !== null) return self::$state;
        $app = Factory::getApplication();
        $userId = (int) $app->getIdentity()->id;
        $service = FavoriteHelper::service();

        return self::$state = [
            'count' => $service->defaultCount($userId),
            'guest' => $userId < 1,
        ];
    }
}
