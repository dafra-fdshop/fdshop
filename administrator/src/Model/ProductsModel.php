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
                'a.sku',
                'gtin',
                'a.gtin',
                'alias',
                'a.alias',
                'manufacturer_name',
                'm.manufacturer_name',
                'active_price_gross',
                'a.active_price_gross',
                'state',
                'a.state',
                'ordering',
                'a.ordering',
                'created',
                'a.created',
                'modified',
                'a.modified',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.ordering', $direction = 'ASC'): void
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
                'GROUP_CONCAT(' . $db->quoteName('c.category_name') . ' ORDER BY ' . $db->quoteName('c.ordering') . ' ASC SEPARATOR ' . $db->quote(', ') . ') AS ' . $db->quoteName('category_names'),
            ])
            ->from($db->quoteName('#__fdshop_product_category_map', 'pcm'))
            ->join('LEFT', $db->quoteName('#__fdshop_categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('pcm.category_id'))
            ->group($db->quoteName('pcm.product_id'));

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.sku'),
            $db->quoteName('a.gtin'),
            $db->quoteName('a.manufacturer_id'),
            $db->quoteName('a.product_name'),
            $db->quoteName('a.alias'),
            $db->quoteName('a.short_description'),
            $db->quoteName('a.description'),
            $db->quoteName('a.active_price_net'),
            $db->quoteName('a.active_price_gross'),
            $db->quoteName('a.active_tax_rate'),
            $db->quoteName('a.currency'),
            $db->quoteName('a.stock_quantity'),
            $db->quoteName('a.reserved_quantity'),
            $db->quoteName('a.min_order_qty'),
            $db->quoteName('a.max_order_qty'),
            $db->quoteName('a.step_order_qty'),
            $db->quoteName('a.is_active'),
            $db->quoteName('a.state'),
            $db->quoteName('a.is_featured'),
            $db->quoteName('a.access'),
            $db->quoteName('a.ordering'),
            $db->quoteName('a.publish_up'),
            $db->quoteName('a.publish_down'),
            $db->quoteName('a.created'),
            $db->quoteName('a.created_by'),
            $db->quoteName('a.modified'),
            $db->quoteName('a.modified_by'),
            $db->quoteName('m.manufacturer_name'),
            $db->quoteName('catmap.category_names'),
        ])
            ->from($db->quoteName('#__fdshop_products', 'a'))
            ->join('LEFT', $db->quoteName('#__fdshop_manufacturers', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('a.manufacturer_id'))
            ->join('LEFT', '(' . $categorySubquery . ') AS ' . $db->quoteName('catmap') . ' ON ' . $db->quoteName('catmap.product_id') . ' = ' . $db->quoteName('a.id'));

        $published = $this->getState('filter.published');

        if ($published !== '') {
            $query->where($db->quoteName('a.state') . ' = ' . (int) $published);
        }

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
            } else {
                $token = '%' . str_replace(' ', '%', $search) . '%';
                $quotedToken = $db->quote($token);

                $query->where(
                    '('
                    . $db->quoteName('a.product_name') . ' LIKE ' . $quotedToken
                    . ' OR ' . $db->quoteName('a.sku') . ' LIKE ' . $quotedToken
                    . ' OR ' . $db->quoteName('a.alias') . ' LIKE ' . $quotedToken
                    . ' OR ' . $db->quoteName('a.gtin') . ' LIKE ' . $quotedToken
                    . ')'
                );
            }
        }

        $orderCol  = $this->state->get('list.ordering', 'a.ordering');
        $orderDirn = strtoupper($this->state->get('list.direction', 'ASC'));

        if (!in_array($orderDirn, ['ASC', 'DESC'], true)) {
            $orderDirn = 'ASC';
        }

        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}