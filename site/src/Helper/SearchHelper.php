<?php
namespace FDShop\Component\FDShop\Site\Helper;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class SearchHelper
{
    public static function config(): object
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery('SELECT search_active, search_category_active, search_suggestion_limit FROM ' . $db->quoteName('#__fdshop_config') . ' WHERE id = 1');

        return $db->loadObject() ?: (object) [
            'search_active' => 1,
            'search_category_active' => 0,
            'search_suggestion_limit' => 8,
        ];
    }

    public static function enabled(): bool
    {
        return (int) self::config()->search_active === 1;
    }
}
