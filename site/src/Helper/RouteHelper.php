<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

final class RouteHelper
{
    public static function getCategoryRoute(int $categoryId): string
    {
        return Route::_('index.php?option=com_fdshop&view=category&id=' . $categoryId);
    }

    public static function getProductRoute(int $productId, int $categoryId): string
    {
        return Route::_(
            'index.php?option=com_fdshop&view=product&id=' . $productId . '&catid=' . $categoryId
        );
    }

    public static function getManufacturerRoute(int $manufacturerId, string $alias = ''): string
    {
        $segment = (string) $manufacturerId;

        if ($alias !== '') {
            $segment .= ':' . $alias;
        }

        return Route::_('index.php?option=com_fdshop&view=manufacturer&id=' . rawurlencode($segment));
    }

    public static function getCartRoute(): string
    {
        return Route::_('index.php?option=com_fdshop&view=cart');
    }
}
