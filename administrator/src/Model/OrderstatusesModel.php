<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

class OrderstatusesModel extends ConfigurationListModel
{
    protected string $requestScope = 'orderstatuses';

    protected $filterFormName = 'filter_orderstatuses';

    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id',
                'a.id',
                'status_code',
                'a.status_code',
                'status_name',
                'a.status_name',
                'seller_email_mode',
                'a.seller_email_mode',
                'notify_buyer',
                'a.notify_buyer',
                'create_invoice',
                'a.create_invoice',
                'stock_action',
                'a.stock_action',
                'ordering',
                'a.ordering',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.ordering', $direction = 'ASC'): void
    {
        $this->populateScopedState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.status_code'),
            $db->quoteName('a.status_name'),
            $db->quoteName('a.seller_email_mode'),
            $db->quoteName('a.seller_email_address'),
			$db->quoteName('a.notify_buyer'),
            $db->quoteName('a.create_invoice'),
            $db->quoteName('a.stock_action'),
            $db->quoteName('a.ordering'),
        ])
            ->from($db->quoteName('#__fdshop_order_statuses', 'a'));

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
            } else {
                $token = $db->quote('%' . str_replace(' ', '%', $search) . '%');
                $query->where(
                    '('
                    . $db->quoteName('a.status_name') . ' LIKE ' . $token
                    . ' OR ' . $db->quoteName('a.status_code') . ' LIKE ' . $token
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
        $query->order($db->quoteName('a.id') . ' ASC');

        return $query;
    }
}
