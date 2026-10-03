<?php

namespace FDShop\Component\FDShop\Site\Helper;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Service\CartServiceInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

final class CartSummaryHelper
{
    private static ?array $summary = null;

    public static function get(): array
    {
        if (self::$summary !== null) return self::$summary;
        $app = Factory::getApplication();
        $session = $app->getSession();
        $cart = $app->bootComponent('com_fdshop')->getContainer()->get(CartServiceInterface::class)
            ->getSummary((int) $app->getIdentity()->id, $session->getId());
        $currency = (string) ($cart['currency'] ?? 'EUR');
        $positions = [];
        $count = 0.0;

        foreach ($cart['items'] as $item) {
            $quantity = (float) $item->quantity;
            $count += $quantity;
            $positions[] = [
                'type' => 'product',
                'name' => (string) $item->sales_name,
                'quantity' => self::quantity($quantity),
                'total' => self::money((float) $item->line_total, (string) $item->currency),
                'image' => (string) $item->image !== '' ? rtrim(Uri::root(true), '/') . '/' . ltrim((string) $item->image, '/') : rtrim(Uri::root(true), '/') . '/media/com_fdshop/images/product-placeholder.svg',
                'url' => RouteHelper::getProductRoute((int) $item->product_id, (int) $item->category_id),
            ];
        }
        foreach ($cart['bundles'] as $bundle) {
            $count += 1;
            $positions[] = [
                'type' => 'bundle',
                'name' => (string) $bundle->bundle_name,
                'quantity' => '1',
                'total' => self::money((float) $bundle->total_gross, (string) $bundle->currency),
                'image' => rtrim(Uri::root(true), '/') . '/media/com_fdshop/images/product-placeholder.svg',
                'url' => '',
            ];
        }

        return self::$summary = [
            'positions' => $positions,
            'count' => $count,
            'countFormatted' => self::quantity($count),
            'subtotal' => self::money((float) $cart['subtotal'], $currency),
            'empty' => $positions === [],
        ];
    }

    private static function quantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }

    private static function money(float $value, string $currency): string
    {
        $code = strtoupper($currency ?: 'EUR');
        return number_format($value, 2, ',', '.') . ' ' . ($code === 'EUR' ? '€' : $code);
    }
}
