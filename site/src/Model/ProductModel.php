<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

final class ProductModel extends BaseDatabaseModel
{
    public function getItem(): ?object
    {
        $productId = max(0, Factory::getApplication()->getInput()->getInt('id'));

        if ($productId < 1) {
            return null;
        }

        $db = $this->getDatabase();
        $now = Factory::getDate()->toSql();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('p.id'),
                $db->quoteName('p.product_name'),
                $db->quoteName('p.alias'),
                $db->quoteName('p.short_description'),
                $db->quoteName('p.description'),
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
                $db->quoteName('p.meta_title'),
                $db->quoteName('p.meta_keywords'),
                $db->quoteName('p.meta_description'),
                $db->quoteName('m.id', 'manufacturer_id'),
                $db->quoteName('m.manufacturer_name'),
                $db->quoteName('m.alias', 'manufacturer_alias'),
            ])
            ->from($db->quoteName('#__fdshop_products', 'p'))
            ->leftJoin($db->quoteName('#__fdshop_manufacturers', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('p.manufacturer_id') . ' AND ' . $db->quoteName('m.is_active') . ' = 1')
            ->where($db->quoteName('p.id') . ' = :productId')
            ->where($db->quoteName('p.is_active') . ' = 1')
            ->where($db->quoteName('p.is_deleted') . ' = 0')
            ->where('(' . $db->quoteName('p.publish_up') . ' IS NULL OR ' . $db->quoteName('p.publish_up') . ' <= :publishUp)')
            ->where('(' . $db->quoteName('p.publish_down') . ' IS NULL OR ' . $db->quoteName('p.publish_down') . ' >= :publishDown)')
            ->bind(':productId', $productId, ParameterType::INTEGER)
            ->bind(':publishUp', $now)
            ->bind(':publishDown', $now);

        $db->setQuery($query);
        $item = $db->loadObject();

        if (!$item) {
            return null;
        }

        $item->has_discount = (int) $item->discount_active === 1 && (float) $item->discount_price > 0;
        $item->current_price = $item->has_discount ? (float) $item->discount_price : (float) $item->sale_price;
        $item->media = $this->getMedia($productId);

        return $item;
    }

    private function getMedia(int $productId): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('media_type'), $db->quoteName('external_url'),
                $db->quoteName('path_standard'), $db->quoteName('path_small'),
                $db->quoteName('path_mobile'), $db->quoteName('is_primary'),
                $db->quoteName('ordering'),
            ])
            ->from($db->quoteName('#__fdshop_media'))
            ->where($db->quoteName('product_id') . ' = :mediaProductId')
            ->whereIn($db->quoteName('media_type'), ['image', 'youtube'], ParameterType::STRING)
            ->bind(':mediaProductId', $productId, ParameterType::INTEGER)
            ->order($db->quoteName('is_primary') . ' DESC')
            ->order($db->quoteName('ordering') . ' ASC')
            ->order($db->quoteName('id') . ' ASC');
        $db->setQuery($query);
        $media = ['images' => [], 'video' => null, 'video_id' => null];

        foreach ($db->loadObjectList() as $medium) {
            if ($medium->media_type === 'image') {
                $path = trim((string) ($medium->path_standard ?: $medium->path_small ?: $medium->path_mobile));
                if ($path !== '') {
                    $media['images'][] = $path;
                }
            } elseif ($medium->media_type === 'youtube' && $media['video'] === null) {
                $videoId = $this->extractYouTubeId((string) $medium->external_url);
                if ($videoId !== null) {
                    $media['video_id'] = $videoId;
                    $media['video'] = 'https://www.youtube-nocookie.com/embed/' . $videoId;
                }
            }
        }

        return $media;
    }

    private function extractYouTubeId(string $url): ?string
    {
        $parts = parse_url(trim($url));
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

        return preg_match('/^[A-Za-z0-9_-]{6,}$/', $videoId) ? $videoId : null;
    }
}
