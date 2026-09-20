<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Site\Helper;

defined('_JEXEC') or die;

final class ProductVisualHelper
{
    public static function backgroundState(object $product): string
    {
        if ((int) ($product->discount_active ?? 0) === 1 && (float) ($product->discount_price ?? 0) > 0) {
            return 'action';
        }

        if ((int) ($product->ribbon_new ?? 0) === 1) {
            return 'new';
        }

        if ((int) ($product->ribbon_bundle ?? 0) === 1) {
            return 'bundle';
        }

        return 'standard';
    }
}
