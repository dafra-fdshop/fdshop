<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

class BundlesModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id',
                'a.id',
                'bundle_number',
                'a.bundle_number',
                'bundle_name',
                'a.bundle_name',
                'alias',
                'a.alias',
                'description',
                'a.description',
                'published',
                'a.is_active',
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
            $db->quoteName('a.bundle_number'),
            $db->quoteName('a.bundle_name'),
            $db->quoteName('a.alias'),
            $db->quoteName('a.description'),
            $db->quoteName('a.is_active', 'published'),
            $db->quoteName('a.is_active'),
            $db->quoteName('a.created'),
            $db->quoteName('a.created_by'),
            $db->quoteName('a.modified'),
            $db->quoteName('a.modified_by'),
        ])
            ->from($db->quoteName('#__fdshop_bundles', 'a'));

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
                    . $db->quoteName('a.bundle_number') . ' LIKE ' . $quotedToken
                    . ' OR ' . $db->quoteName('a.bundle_name') . ' LIKE ' . $quotedToken
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