<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class StorageLocationsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'location_name', 'a.location_name',
                'code', 'a.code',
                'published', 'a.is_active',
                'is_active',
                'ordering', 'a.ordering',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.ordering', $direction = 'ASC'): void
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $published = $this->getUserStateFromRequest(
            $this->context . '.filter.published',
            'filter_published',
            ''
        );
        $this->setState('filter.published', $published);

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('a.id'),
                $db->quoteName('a.location_name'),
                $db->quoteName('a.code'),
                $db->quoteName('a.description'),
                $db->quoteName('a.is_active', 'published'),
                $db->quoteName('a.ordering'),
                $db->quoteName('a.created'),
                $db->quoteName('a.created_by'),
                $db->quoteName('a.modified'),
                $db->quoteName('a.modified_by'),
            ])
            ->from($db->quoteName('#__fdshop_storage_locations', 'a'));

        $published = $this->getState('filter.published');

        if ($published !== '') {
            $query->where($db->quoteName('a.is_active') . ' = ' . (int) $published);
        }

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
            } else {
                $token = $db->quote('%' . str_replace(' ', '%', $search) . '%');
                $query->where(
                    '('
                    . $db->quoteName('a.location_name') . ' LIKE ' . $token
                    . ' OR ' . $db->quoteName('a.code') . ' LIKE ' . $token
                    . ' OR ' . $db->quoteName('a.description') . ' LIKE ' . $token
                    . ')'
                );
            }
        }

        $orderCol = $this->state->get('list.ordering', 'a.ordering');
        $orderDirn = strtoupper($this->state->get('list.direction', 'ASC'));

        if (!in_array($orderDirn, ['ASC', 'DESC'], true)) {
            $orderDirn = 'ASC';
        }

        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));
        $query->order($db->quoteName('a.id') . ' DESC');

        return $query;
    }
}
