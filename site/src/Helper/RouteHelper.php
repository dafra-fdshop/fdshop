<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

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

    public static function getProductCanonicalRoute(int $productId, int $categoryId = 0): string
    {
        $query = 'index.php?option=com_fdshop&view=product&id=' . $productId;

        if ($categoryId > 0) {
            $query .= '&catid=' . $categoryId;
        }

        $route = (string) Route::_($query);

        if (preg_match('~^https?://~i', $route) === 1) {
            return $route;
        }

        return rtrim(Uri::root(), '/') . '/' . ltrim($route, '/');
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
