<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

class OrdersModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id',
                'a.id',
                'order_number',
                'a.order_number',
                'user_id',
                'a.user_id',
                'customer_name',
                'u.name',
                'customer_email',
                'u.email',
                'order_status_id',
                'a.order_status_id',
                'status_name',
                'os.status_name',
                'payment_name',
                'pm.payment_name',
                'shipment_name',
                's.shipment_name',
                'shipment_color',
                's.shipment_color',
                'grand_total',
                'a.grand_total',
                'currency',
                'a.currency',
                'state',
                'a.state',
                'created',
                'a.created',
                'modified',
                'a.modified',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.created', $direction = 'DESC'): void
    {
        $app = Factory::getApplication();

        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $status = $this->getUserStateFromRequest($this->context . '.filter.status', 'filter_status', '');
        $this->setState('filter.status', $status);

        parent::populateState($ordering, $direction);
    }
	
	public function getStatusOptions(): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select('id AS value, status_name AS text')
			->from('#__fdshop_order_statuses')
			->order('ordering ASC, id ASC');

		$db->setQuery($query);

		return $db->loadObjectList();
	}

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.order_number'),
            $db->quoteName('a.user_id'),
            $db->quoteName('a.buyer_group_id'),
            $db->quoteName('a.payment_method_id'),
            $db->quoteName('a.shipment_id'),
            $db->quoteName('a.order_status'),
            $db->quoteName('a.order_status_id'),
            $db->quoteName('a.state'),
            $db->quoteName('a.currency'),
            $db->quoteName('a.grand_total'),
            $db->quoteName('a.created'),
            $db->quoteName('a.modified'),
            $db->quoteName('u.name', 'customer_name'),
            $db->quoteName('u.email', 'customer_email'),
            $db->quoteName('os.status_name'),
            $db->quoteName('os.status_code'),
            $db->quoteName('pm.payment_name'),
            $db->quoteName('s.shipment_name'),
            $db->quoteName('s.shipment_color'),
            'CASE WHEN ' . $db->quoteName('os.status_code') . ' IN (' . $db->quote('paid') . ', ' . $db->quote('shipped') . ', ' . $db->quote('completed') . ') THEN 1 ELSE 0 END AS ' . $db->quoteName('is_paid'),
        ])
            ->from($db->quoteName('#__fdshop_orders', 'a'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.user_id'))
            ->join('LEFT', $db->quoteName('#__fdshop_order_statuses', 'os') . ' ON ' . $db->quoteName('os.id') . ' = ' . $db->quoteName('a.order_status_id'))
            ->join('LEFT', $db->quoteName('#__fdshop_payment_methods', 'pm') . ' ON ' . $db->quoteName('pm.id') . ' = ' . $db->quoteName('a.payment_method_id'))
            ->join('LEFT', $db->quoteName('#__fdshop_shipments', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('a.shipment_id'));

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
            } else {
                $token = '%' . str_replace(' ', '%', $search) . '%';

                $query->where(
                    $db->quoteName('a.order_number') . ' LIKE ' . $db->quote($token)
                );
            }
        }

        $status = $this->getState('filter.status');

        if ($status !== '') {
            $query->where($db->quoteName('a.order_status_id') . ' = ' . (int) $status);
        }

        $orderCol  = $this->state->get('list.ordering', 'a.created');
        $orderDirn = strtoupper($this->state->get('list.direction', 'DESC'));

        if (!in_array($orderDirn, ['ASC', 'DESC'], true)) {
            $orderDirn = 'DESC';
        }

        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}