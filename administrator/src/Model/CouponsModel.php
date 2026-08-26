<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class CouponsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id',
                'a.id',
                'coupon_code',
                'a.coupon_code',
                'coupon_name',
                'a.coupon_name',
                'alias',
                'a.alias',
                'description',
                'a.description',
                'discount_type',
                'a.discount_type',
                'discount_value',
                'a.discount_value',
                'minimum_order_total',
                'a.minimum_order_total',
                'usage_limit_total',
                'a.usage_limit_total',
                'usage_limit_per_user',
                'a.usage_limit_per_user',
                'valid_from',
                'a.valid_from',
                'valid_to',
                'a.valid_to',
                'published',
                'a.published',
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

    protected function populateState($ordering = 'a.id', $direction = 'DESC'): void
    {
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

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.coupon_code'),
            $db->quoteName('a.coupon_name'),
            $db->quoteName('a.alias'),
            $db->quoteName('a.description'),
            $db->quoteName('a.discount_type'),
            $db->quoteName('a.discount_value'),
            $db->quoteName('a.minimum_order_total'),
            $db->quoteName('a.usage_limit_total'),
            $db->quoteName('a.usage_limit_per_user'),
            $db->quoteName('a.valid_from'),
            $db->quoteName('a.valid_to'),
            $db->quoteName('a.published'),
            $db->quoteName('a.ordering'),
            $db->quoteName('a.created'),
            $db->quoteName('a.created_by'),
            $db->quoteName('a.modified'),
            $db->quoteName('a.modified_by'),
        ])
            ->from($db->quoteName('#__fdshop_coupons', 'a'));

        $published = $this->getState('filter.published');

        if ($published !== '') {
            $query->where($db->quoteName('a.published') . ' = ' . (int) $published);
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
                    . $db->quoteName('a.coupon_code') . ' LIKE ' . $quotedToken
                    . ' OR ' . $db->quoteName('a.coupon_name') . ' LIKE ' . $quotedToken
                    . ' OR ' . $db->quoteName('a.alias') . ' LIKE ' . $quotedToken
                    . ' OR ' . $db->quoteName('a.description') . ' LIKE ' . $quotedToken
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
