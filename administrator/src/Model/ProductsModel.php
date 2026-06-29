<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

class ProductsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id',
                'a.id',
                'product_name',
                'a.product_name',
                'sku',
                'd.sku',
                'gtin',
                'd.gtin',
                'alias',
                'a.alias',
                'manufacturer_name',
                'm.manufacturer_name',
                'sale_price',
                'a.sale_price',
                'discount_price',
                'a.discount_price',
                'in_stock',
                'a.in_stock',
                'is_active',
                'a.is_active',
                'created',
                'a.created',
                'modified',
                'a.modified',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.id', $direction = 'DESC'): void
    {
        $app = Factory::getApplication();

        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $categorySubquery = $db->getQuery(true)
            ->select([
                $db->quoteName('pcm.product_id'),
                'GROUP_CONCAT('
                    . $db->quoteName('c.category_name')
                    . ' ORDER BY ' . $db->quoteName('c.ordering') . ' ASC SEPARATOR '
                    . $db->quote(', ')
                    . ') AS ' . $db->quoteName('category_names'),
            ])
            ->from($db->quoteName('#__fdshop_product_category_map', 'pcm'))
            ->join(
                'LEFT',
                $db->quoteName('#__fdshop_categories', 'c')
                . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('pcm.category_id')
            )
            ->group($db->quoteName('pcm.product_id'));

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.manufacturer_id'),
            $db->quoteName('a.product_name'),
            $db->quoteName('a.alias'),
            $db->quoteName('a.short_description'),
            $db->quoteName('a.description'),
            $db->quoteName('a.buyer_group_id'),
            $db->quoteName('a.sale_price'),
            $db->quoteName('a.discount_price'),
            $db->quoteName('a.discount_active'),
            $db->quoteName('a.currency'),
            $db->quoteName('a.min_order_qty'),
            $db->quoteName('a.max_order_qty'),
            $db->quoteName('a.step_order_qty'),
            $db->quoteName('a.is_active'),
            $db->quoteName('a.publish_up'),
            $db->quoteName('a.publish_down'),
            $db->quoteName('a.meta_title'),
            $db->quoteName('a.meta_keywords'),
            $db->quoteName('a.meta_description'),
            $db->quoteName('a.in_stock'),
            $db->quoteName('a.available_from'),
            $db->quoteName('a.unit_type'),
            $db->quoteName('a.unit_quantity'),
            $db->quoteName('a.nem'),
            $db->quoteName('a.shot_count'),
            $db->quoteName('a.caliber'),
            $db->quoteName('a.burn_time'),
            $db->quoteName('a.rise_height'),
            $db->quoteName('a.ribbon_new'),
            $db->quoteName('a.ribbon_hot'),
            $db->quoteName('d.sku'),
            $db->quoteName('d.gtin'),
            $db->quoteName('d.stock_quantity'),
            $db->quoteName('d.low_stock'),
            $db->quoteName('d.reserved_quantity'),
            $db->quoteName('d.sold_quantity'),
            $db->quoteName('d.is_in_stock'),
            $db->quoteName('d.weight'),
            $db->quoteName('d.length'),
            $db->quoteName('d.width'),
            $db->quoteName('d.height'),
            $db->quoteName('m.manufacturer_name'),
            $db->quoteName('catmap.category_names'),
        ])
            ->from($db->quoteName('#__fdshop_products', 'a'))
            ->join(
                'LEFT',
                $db->quoteName('#__fdshop_products_details', 'd')
                . ' ON ' . $db->quoteName('d.product_id') . ' = ' . $db->quoteName('a.id')
            )
            ->join(
                'LEFT',
                $db->quoteName('#__fdshop_manufacturers', 'm')
                . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('a.manufacturer_id')
            )
            ->join(
                'LEFT',
                '(' . $categorySubquery . ') AS ' . $db->quoteName('catmap')
                . ' ON ' . $db->quoteName('catmap.product_id') . ' = ' . $db->quoteName('a.id')
            );

        $published = $this->getState('filter.published');

        if ($published !== '') {
            $query->where($db->quoteName('a.is_active') . ' = ' . (int) $published);
        }

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
            } else {
                $token       = '%' . str_replace(' ', '%', $search) . '%';
                $quotedToken = $db->quote($token);

                $query->where(
                    '('
                    . $db->quoteName('a.product_name') . ' LIKE ' . $quotedToken
                    . ' OR ' . $db->quoteName('d.sku') . ' LIKE ' . $quotedToken
                    . ' OR ' . $db->quoteName('a.alias') . ' LIKE ' . $quotedToken
                    . ' OR ' . $db->quoteName('d.gtin') . ' LIKE ' . $quotedToken
                    . ' OR ' . $db->quoteName('a.in_stock') . ' LIKE ' . $quotedToken
                    . ')'
                );
            }
        }

        $orderCol  = $this->state->get('list.ordering', 'a.id');
        $orderDirn = strtoupper($this->state->get('list.direction', 'DESC'));

        if (!in_array($orderDirn, ['ASC', 'DESC'], true)) {
            $orderDirn = 'DESC';
        }

        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}