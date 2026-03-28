<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class ShipmentsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id',
                'a.id',
                'shipment_name',
                'a.shipment_name',
                'shipment_description',
                'a.shipment_description',
                'shipment_color',
                'a.shipment_color',
                'shipment_price',
                'a.shipment_price',
                'published',
                'a.is_published',
                'ordering',
                'a.ordering',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.ordering', $direction = 'ASC'): void
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
            $db->quoteName('a.shipment_name'),
            $db->quoteName('a.shipment_description'),
            $db->quoteName('a.shipment_color'),
            $db->quoteName('a.shipment_price'),
            $db->quoteName('a.is_published', 'published'),
            $db->quoteName('a.ordering'),
            $db->quoteName('a.created'),
            $db->quoteName('a.created_by'),
            $db->quoteName('a.modified'),
            $db->quoteName('a.modified_by'),
        ])
            ->from($db->quoteName('#__fdshop_shipments', 'a'));

        $published = $this->getState('filter.published');

        if ($published !== '') {
            $query->where($db->quoteName('a.is_published') . ' = ' . (int) $published);
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
                    . $db->quoteName('a.shipment_name') . ' LIKE ' . $quotedToken
                    . ' OR ' . $db->quoteName('a.shipment_description') . ' LIKE ' . $quotedToken
                    . ' OR ' . $db->quoteName('a.shipment_color') . ' LIKE ' . $quotedToken
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
        $query->order($db->quoteName('a.id') . ' DESC');

        return $query;
    }
}