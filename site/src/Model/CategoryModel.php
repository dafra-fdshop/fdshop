<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;

final class CategoryModel extends ListModel
{
    private const ORDER_FIELDS = [
        'name'  => 'p.product_name',
        'price' => 'current_price',
    ];

    private const ALLOWED_LIMITS = [12, 24, 36, 48];

    private ?object $category = null;

    protected function populateState($ordering = 'p.product_name', $direction = 'ASC'): void
    {
        $input = Factory::getApplication()->getInput();
        $sort = strtolower($input->getCmd('sort', 'name'));
        $sort = isset(self::ORDER_FIELDS[$sort]) ? $sort : 'name';
        $dir = strtoupper($input->getCmd('dir', 'ASC'));
        $dir = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'ASC';
        $limit = $input->getInt('limit', 24);
        $limit = in_array($limit, self::ALLOWED_LIMITS, true) ? $limit : 24;
        $start = max(0, $input->getInt('limitstart', 0));
        $start = (int) floor($start / $limit) * $limit;

        $this->setState('category.id', max(0, $input->getInt('id')));
        $this->setState('list.ordering', self::ORDER_FIELDS[$sort]);
        $this->setState('list.direction', $dir);
        $this->setState('list.limit', $limit);
        $this->setState('list.start', $start);
        $this->setState('filter.sort', $sort);
        $this->setState('filter.direction', strtolower($dir));
    }

    public function getCategory(): ?object
    {
        if ($this->category !== null) {
            return $this->category;
        }

        $categoryId = (int) $this->getState('category.id');

        if ($categoryId < 1) {
            return null;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('category_name'),
                $db->quoteName('alias'),
                $db->quoteName('description'),
            ])
            ->from($db->quoteName('#__fdshop_categories'))
            ->where($db->quoteName('id') . ' = :categoryId')
            ->where($db->quoteName('is_active') . ' = 1')
            ->bind(':categoryId', $categoryId, ParameterType::INTEGER);

        $db->setQuery($query);
        $category = $db->loadObject();
        $this->category = $category ?: null;

        return $this->category;
    }

    protected function getListQuery(): DatabaseQuery
    {
        $db = $this->getDatabase();
        $currentPrice = 'CASE WHEN ' . $db->quoteName('p.discount_active') . ' = 1'
            . ' AND ' . $db->quoteName('p.discount_price') . ' > 0'
            . ' THEN ' . $db->quoteName('p.discount_price')
            . ' ELSE ' . $db->quoteName('p.sale_price') . ' END';
        $now = Factory::getDate()->toSql();
        $categoryId = (int) $this->getState('category.id');
        $ordering = (string) $this->getState('list.ordering', 'p.product_name');
        $direction = (string) $this->getState('list.direction', 'ASC');

        if (!in_array($ordering, self::ORDER_FIELDS, true)) {
            $ordering = self::ORDER_FIELDS['name'];
        }

        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'ASC';
        }

        return $db->getQuery(true)
            ->select([
                $db->quoteName('p.id'),
                $db->quoteName('p.product_name'),
                $db->quoteName('p.alias'),
                $db->quoteName('p.short_description'),
                $db->quoteName('p.sale_price'),
                $db->quoteName('p.discount_price'),
                $db->quoteName('p.discount_active'),
                $db->quoteName('p.currency'),
                $db->quoteName('p.in_stock'),
                $db->quoteName('p.nem'),
                $db->quoteName('p.shot_count'),
                $db->quoteName('p.caliber'),
                $db->quoteName('p.burn_time'),
                $db->quoteName('p.rise_height'),
                $db->quoteName('p.ribbon_new'),
                $db->quoteName('p.ribbon_hot'),
                $db->quoteName('p.ribbon_bundle'),
                $currentPrice . ' AS ' . $db->quoteName('current_price'),
            ])
            ->from($db->quoteName('#__fdshop_products', 'p'))
            ->innerJoin(
                $db->quoteName('#__fdshop_product_category_map', 'pcm')
                . ' ON ' . $db->quoteName('pcm.product_id') . ' = ' . $db->quoteName('p.id')
            )
            ->where($db->quoteName('pcm.category_id') . ' = :categoryId')
            ->where($db->quoteName('p.is_active') . ' = 1')
            ->where($db->quoteName('p.is_deleted') . ' = 0')
            ->where('(' . $db->quoteName('p.publish_up') . ' IS NULL OR ' . $db->quoteName('p.publish_up') . ' <= :publishUp)')
            ->where('(' . $db->quoteName('p.publish_down') . ' IS NULL OR ' . $db->quoteName('p.publish_down') . ' >= :publishDown)')
            ->bind(':categoryId', $categoryId, ParameterType::INTEGER)
            ->bind(':publishUp', $now)
            ->bind(':publishDown', $now)
            ->order($ordering . ' ' . $direction)
            ->order($db->quoteName('p.id') . ' ASC');
    }

    public function getItems(): array
    {
        $items = parent::getItems();

        if (!$items) {
            return [];
        }

        $media = $this->getMediaForProducts(array_map(static fn ($item): int => (int) $item->id, $items));

        foreach ($items as $item) {
            $item->current_price = (float) $item->current_price;
            $item->has_discount = (int) $item->discount_active === 1 && (float) $item->discount_price > 0;
            $item->media = $media[(int) $item->id] ?? ['image' => null, 'video' => null];
        }

        return $items;
    }

    private function getMediaForProducts(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));

        if ($productIds === []) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('product_id'),
                $db->quoteName('media_type'),
                $db->quoteName('external_url'),
                $db->quoteName('path_standard'),
                $db->quoteName('path_small'),
                $db->quoteName('path_mobile'),
                $db->quoteName('is_primary'),
                $db->quoteName('ordering'),
            ])
            ->from($db->quoteName('#__fdshop_media'))
            ->whereIn($db->quoteName('product_id'), $productIds)
            ->whereIn($db->quoteName('media_type'), ['image', 'video'], ParameterType::STRING)
            ->order($db->quoteName('product_id') . ' ASC')
            ->order($db->quoteName('is_primary') . ' DESC')
            ->order($db->quoteName('ordering') . ' ASC')
            ->order($db->quoteName('id') . ' ASC');

        $db->setQuery($query);
        $grouped = [];

        foreach ($db->loadObjectList() as $medium) {
            $productId = (int) $medium->product_id;
            $grouped[$productId] ??= ['image' => null, 'video' => null];

            if ($medium->media_type === 'image' && $grouped[$productId]['image'] === null) {
                $path = trim((string) ($medium->path_standard ?: $medium->path_small ?: $medium->path_mobile));
                $grouped[$productId]['image'] = $path !== '' ? $path : null;
            }

            if ($medium->media_type === 'video' && $grouped[$productId]['video'] === null) {
                $grouped[$productId]['video'] = $this->normaliseVideoUrl((string) $medium->external_url);
            }
        }

        return $grouped;
    }

    private function normaliseVideoUrl(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $videoId = '';

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $videoId = trim((string) ($parts['path'] ?? ''), '/');
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            if (preg_match('~^/(?:embed|shorts)/([A-Za-z0-9_-]{6,})~', (string) ($parts['path'] ?? ''), $matches)) {
                $videoId = $matches[1];
            } else {
                parse_str((string) ($parts['query'] ?? ''), $query);
                $videoId = (string) ($query['v'] ?? '');
            }
        }

        if (!preg_match('/^[A-Za-z0-9_-]{6,}$/', $videoId)) {
            return null;
        }

        return 'https://www.youtube-nocookie.com/embed/' . $videoId;
    }
}
