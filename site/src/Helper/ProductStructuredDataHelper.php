<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

final class ProductStructuredDataHelper
{
    private const IN_STOCK_STATUSES = [
        'Verfügbar',
        'Bestellbar',
        'wenige Verfügbar',
        'wenige Bestellbar',
    ];

    public static function build(object $item, string $productUrl, bool $purchaseEnabled): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => trim((string) $item->product_name),
            'url' => $productUrl,
        ];

        $sku = trim((string) ($item->sku ?? ''));
        if ($sku !== '') {
            $data['sku'] = $sku;
        }

        $description = self::description($item);
        if ($description !== '') {
            $data['description'] = $description;
        }

        $images = (array) (($item->media ?? [])['images'] ?? []);
        $image = self::publicUrl((string) ($images[0] ?? ''));
        if ($image !== '') {
            $data['image'] = $image;
        }

        $brand = trim((string) ($item->manufacturer_name ?? ''));
        if ($brand !== '') {
            $data['brand'] = ['@type' => 'Brand', 'name' => $brand];
        }

        $price = (float) ($item->current_price ?? 0);
        $currency = strtoupper(trim((string) ($item->currency ?? '')));
        if ($purchaseEnabled && $price > 0 && preg_match('/^[A-Z]{3}$/', $currency) === 1) {
            $availability = in_array((string) ($item->in_stock ?? ''), self::IN_STOCK_STATUSES, true)
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock';
            $data['offers'] = [
                '@type' => 'Offer',
                'url' => $productUrl,
                'price' => round($price, 2),
                'priceCurrency' => $currency,
                'availability' => $availability,
            ];
        }

        return $data;
    }

    public static function encode(array $data): string
    {
        return json_encode(
            $data,
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
            | JSON_THROW_ON_ERROR
        );
    }

    private static function description(object $item): string
    {
        $source = trim((string) ($item->short_description ?? ''));
        if ($source === '') {
            $source = trim((string) ($item->description ?? ''));
        }

        $source = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', ' ', $source) ?? '';
        $source = html_entity_decode(strip_tags($source), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $source) ?? '');
    }

    private static function publicUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (preg_match('~^https?://~i', $path) === 1) {
            return $path;
        }

        return rtrim(Uri::root(), '/') . '/' . ltrim($path, '/');
    }
}
