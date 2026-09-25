<?php

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Helper\ProductCardHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class ModFdshopProductsHelper
{
    private static array $requestCache = [];

    public static function getData(object $params): array
    {
        $categoryId = max(0, (int) $params->get('category_id', 0));
        $limit = min(25, max(5, (int) $params->get('max_products', 20)));
        $sorting = (string) $params->get('sorting', 'newest');
        $cacheKey = implode(':', [$categoryId, $limit, $sorting]);

        if (isset(self::$requestCache[$cacheKey])) {
            return array_replace(self::$requestCache[$cacheKey], ['request_cache_hit' => true, 'query_count' => 0, 'render_ms' => 0.0]);
        }

        $started = microtime(true);
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $category = self::category($db, $categoryId);

        if ($category === null) {
            return self::$requestCache[$cacheKey] = ['category' => null, 'items' => [], 'query_count' => 1, 'render_ms' => (microtime(true) - $started) * 1000, 'request_cache_hit' => false];
        }

        $items = self::products($db, $categoryId, $limit, $sorting);
        $media = self::media($db, array_map(static fn (object $item): int => (int) $item->id, $items));

        foreach ($items as $item) {
            $item->media = $media[(int) $item->id] ?? ['standard' => null, 'small' => null, 'mobile' => null, 'video' => null];
            ProductCardHelper::prepare($item, $categoryId);
        }

        return self::$requestCache[$cacheKey] = [
            'category' => $category,
            'items' => $items,
            'query_count' => 3,
            'render_ms' => (microtime(true) - $started) * 1000,
            'request_cache_hit' => false,
        ];
    }

    private static function category(DatabaseInterface $db, int $categoryId): ?object
    {
        if ($categoryId < 1) {
            return null;
        }
        $query = $db->getQuery(true)->select($db->quoteName(['id', 'category_name', 'alias']))
            ->from($db->quoteName('#__fdshop_categories'))
            ->where($db->quoteName('id') . ' = :categoryId')->where($db->quoteName('is_active') . ' = 1')
            ->bind(':categoryId', $categoryId, ParameterType::INTEGER);
        $db->setQuery($query);
        return $db->loadObject() ?: null;
    }

    private static function products(DatabaseInterface $db, int $categoryId, int $limit, string $sorting): array
    {
        $currentPrice = 'CASE WHEN ' . $db->quoteName('p.discount_active') . ' = 1 AND ' . $db->quoteName('p.discount_price') . ' > 0 THEN ' . $db->quoteName('p.discount_price') . ' ELSE ' . $db->quoteName('p.sale_price') . ' END';
        $now = Factory::getDate()->toSql();
        $order = match ($sorting) {
            'name_asc' => $db->quoteName('p.product_name') . ' ASC',
            'price_asc' => $db->quoteName('current_price') . ' ASC',
            'price_desc' => $db->quoteName('current_price') . ' DESC',
            'random' => 'RAND()',
            default => $db->quoteName('p.id') . ' DESC',
        };
        $fields = ['id', 'product_name', 'alias', 'short_description', 'sale_price', 'discount_price', 'discount_active', 'currency', 'in_stock', 'unit_type', 'min_order_qty', 'max_order_qty', 'step_order_qty', 'nem', 'shot_count', 'caliber', 'burn_time', 'rise_height', 'ribbon_new', 'ribbon_hot', 'ribbon_bundle'];
        $query = $db->getQuery(true)->select(array_map(static fn (string $field): string => $db->quoteName('p.' . $field), $fields))
            ->select($currentPrice . ' AS ' . $db->quoteName('current_price'))
            ->from($db->quoteName('#__fdshop_products', 'p'))
            ->innerJoin($db->quoteName('#__fdshop_product_category_map', 'pcm') . ' ON ' . $db->quoteName('pcm.product_id') . ' = ' . $db->quoteName('p.id'))
            ->where($db->quoteName('pcm.category_id') . ' = :categoryId')
            ->where($db->quoteName('p.is_active') . ' = 1')->where($db->quoteName('p.is_deleted') . ' = 0')
            ->where('(' . $db->quoteName('p.publish_up') . ' IS NULL OR ' . $db->quoteName('p.publish_up') . ' <= :publishUp)')
            ->where('(' . $db->quoteName('p.publish_down') . ' IS NULL OR ' . $db->quoteName('p.publish_down') . ' >= :publishDown)')
            ->bind(':categoryId', $categoryId, ParameterType::INTEGER)->bind(':publishUp', $now)->bind(':publishDown', $now)
            ->order($order)->order($db->quoteName('p.id') . ' ASC');
        $db->setQuery($query, 0, $limit);
        return $db->loadObjectList();
    }

    private static function media(DatabaseInterface $db, array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }
        $query = $db->getQuery(true)->select($db->quoteName(['product_id', 'media_type', 'path_standard', 'path_small', 'path_mobile', 'is_primary', 'ordering']))
            ->from($db->quoteName('#__fdshop_media'))->whereIn($db->quoteName('product_id'), $productIds)
            ->where($db->quoteName('media_type') . ' = ' . $db->quote('image'))
            ->order($db->quoteName('product_id') . ' ASC')->order($db->quoteName('is_primary') . ' DESC')->order($db->quoteName('ordering') . ' ASC')->order($db->quoteName('id') . ' ASC');
        $db->setQuery($query);
        $grouped = [];
        foreach ($db->loadObjectList() as $medium) {
            $id = (int) $medium->product_id;
            if (isset($grouped[$id])) {
                continue;
            }
            $standard = trim((string) ($medium->path_standard ?: $medium->path_small ?: $medium->path_mobile));
            $small = trim((string) ($medium->path_small ?: $medium->path_standard ?: $medium->path_mobile));
            $mobile = trim((string) ($medium->path_mobile ?: $medium->path_small ?: $medium->path_standard));
            $grouped[$id] = ['standard' => $standard ?: null, 'small' => $small ?: null, 'mobile' => $mobile ?: null, 'video' => null];
        }
        return $grouped;
    }
}
