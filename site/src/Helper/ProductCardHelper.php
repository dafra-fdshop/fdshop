<?php

namespace FDShop\Component\FDShop\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

final class ProductCardHelper
{
    public static function prepare(object $item, int $categoryId): object
    {
        $root = rtrim(Uri::root(true), '/');
        $media = $item->media ?? ['standard' => null, 'small' => null, 'mobile' => null, 'video' => null];
        $item->media = $media;
        $item->detail_url = RouteHelper::getProductRoute((int) $item->id, $categoryId);
        $item->image_url = $media['standard'] ? $root . '/' . ltrim((string) $media['standard'], '/') : ProductVisualHelper::placeholderImage();
        $item->image_small_url = $media['small'] ? $root . '/' . ltrim((string) $media['small'], '/') : null;
        $item->image_mobile_url = $media['mobile'] ? $root . '/' . ltrim((string) $media['mobile'], '/') : null;
        $item->image_is_placeholder = $media['standard'] === null;
        $item->current_price = (float) $item->current_price;
        $item->has_discount = (int) $item->discount_active === 1 && (float) $item->discount_price > 0;
        $item->price_formatted = self::formatPrice((float) $item->current_price, (string) $item->currency);
        $item->regular_price_formatted = self::formatPrice((float) $item->sale_price, (string) $item->currency);
        $item->stock_class = match ((string) $item->in_stock) {
            'Verfügbar', 'Bestellbar' => 'fdshop-stock--normal',
            'wenige Verfügbar', 'wenige Bestellbar' => 'fdshop-stock--low',
            default => 'fdshop-stock--none',
        };
        $item->visual_state = ProductVisualHelper::backgroundState($item);
        $item->card_facts = self::facts($item);

        return $item;
    }

    public static function facts(object $item): array
    {
        return [
            ['label' => 'NEM', 'icon' => 'icon_nem.svg', 'value' => (float) $item->nem > 0 ? self::number((float) $item->nem) . ' g' : '-'],
            ['label' => 'Schusszahl', 'icon' => 'icon_anzahl.svg', 'value' => (float) $item->shot_count > 0 ? self::number((float) $item->shot_count) : '-'],
            ['label' => 'Kaliber', 'icon' => 'icon_durchm.svg', 'value' => trim((string) $item->caliber) !== '' && (float) $item->caliber !== 0.0 ? (string) $item->caliber : '-'],
            ['label' => 'Brenndauer', 'icon' => 'icon_zeit.svg', 'value' => trim((string) $item->burn_time) !== '' && (float) $item->burn_time !== 0.0 ? (string) $item->burn_time : '-'],
            ['label' => 'Steighöhe', 'icon' => 'icon_hoehe.svg', 'value' => trim((string) $item->rise_height) !== '' && (float) $item->rise_height !== 0.0 ? (string) $item->rise_height : '-'],
        ];
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }

    private static function formatPrice(float $price, string $currency): string
    {
        return number_format($price, 2, ',', '.') . ' ' . strtoupper(trim($currency ?: 'EUR'));
    }
}
