<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class OrderModel extends BaseDatabaseModel
{
    public function getItem($pk = null): ?object
    {
        $orderId = $this->resolveOrderId($pk);

        if ($orderId <= 0) {
            return null;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.order_number'),
            $db->quoteName('a.user_id'),
            $db->quoteName('a.buyer_group_id'),
            $db->quoteName('a.order_status'),
            $db->quoteName('a.order_status_id'),
            $db->quoteName('a.currency'),
            $db->quoteName('a.grand_total'),
            $db->quoteName('a.created'),
            $db->quoteName('a.modified'),
            $db->quoteName('os.status_name'),
            $db->quoteName('u.name', 'customer_name'),
            $db->quoteName('u.username', 'customer_username'),
            $db->quoteName('u.email', 'customer_email'),
        ])
            ->from($db->quoteName('#__fdshop_orders', 'a'))
            ->join(
                'LEFT',
                $db->quoteName('#__fdshop_order_statuses', 'os')
                . ' ON ' . $db->quoteName('os.id') . ' = ' . $db->quoteName('a.order_status_id')
            )
            ->join(
                'LEFT',
                $db->quoteName('#__users', 'u')
                . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.user_id')
            )
            ->where($db->quoteName('a.id') . ' = ' . (int) $orderId);

        $db->setQuery($query);

        return $db->loadObject() ?: null;
    }

    public function getOrderItems($pk = null): array
    {
        $orderId = $this->resolveOrderId($pk);

        if ($orderId <= 0) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.order_id'),
            $db->quoteName('a.product_id'),
            $db->quoteName('a.product_name'),
            $db->quoteName('a.sku'),
            $db->quoteName('a.quantity'),
            $db->quoteName('a.unit_price_net'),
            $db->quoteName('a.unit_price_gross'),
            '(' . $db->quoteName('a.quantity') . ' * ' . $db->quoteName('a.unit_price_net') . ') AS ' . $db->quoteName('line_total_net'),
            '(' . $db->quoteName('a.quantity') . ' * ' . $db->quoteName('a.unit_price_gross') . ') AS ' . $db->quoteName('line_total_gross'),
        ])
            ->from($db->quoteName('#__fdshop_order_items', 'a'))
            ->where($db->quoteName('a.order_id') . ' = ' . (int) $orderId)
            ->order($db->quoteName('a.id') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    public function getStatusHistory($pk = null): array
    {
        $orderId = $this->resolveOrderId($pk);

        if ($orderId <= 0) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.order_id'),
            $db->quoteName('a.old_status_id'),
            $db->quoteName('a.new_status_id'),
            $db->quoteName('a.comment'),
            $db->quoteName('a.is_system_change'),
            $db->quoteName('a.changed_at'),
            $db->quoteName('a.changed_by'),
            $db->quoteName('old_status.status_name', 'old_status_name'),
            $db->quoteName('new_status.status_name', 'new_status_name'),
        ])
            ->from($db->quoteName('#__fdshop_order_status_history', 'a'))
            ->join(
                'LEFT',
                $db->quoteName('#__fdshop_order_statuses', 'old_status')
                . ' ON ' . $db->quoteName('old_status.id') . ' = ' . $db->quoteName('a.old_status_id')
            )
            ->join(
                'LEFT',
                $db->quoteName('#__fdshop_order_statuses', 'new_status')
                . ' ON ' . $db->quoteName('new_status.id') . ' = ' . $db->quoteName('a.new_status_id')
            )
            ->where($db->quoteName('a.order_id') . ' = ' . (int) $orderId)
            ->order($db->quoteName('a.changed_at') . ' DESC')
            ->order($db->quoteName('a.id') . ' DESC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    public function getOrderHistory($pk = null): array
    {
        $orderId = $this->resolveOrderId($pk);

        if ($orderId <= 0) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.order_id'),
            $db->quoteName('a.event_type'),
            $db->quoteName('a.event_title'),
            $db->quoteName('a.event_text'),
            $db->quoteName('a.reference_type'),
            $db->quoteName('a.reference_id'),
            $db->quoteName('a.is_system_event'),
            $db->quoteName('a.created'),
            $db->quoteName('a.created_by'),
        ])
            ->from($db->quoteName('#__fdshop_order_history', 'a'))
            ->where($db->quoteName('a.order_id') . ' = ' . (int) $orderId)
            ->order($db->quoteName('a.created') . ' DESC')
            ->order($db->quoteName('a.id') . ' DESC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    public function getAvailableProducts(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.product_name'),
            $db->quoteName('a.sku'),
            $db->quoteName('a.active_price_net'),
            $db->quoteName('a.active_price_gross'),
            $db->quoteName('a.currency'),
            $db->quoteName('a.state'),
        ])
            ->from($db->quoteName('#__fdshop_products', 'a'))
            ->where($db->quoteName('a.state') . ' = 1')
            ->order($db->quoteName('a.product_name') . ' ASC')
            ->order($db->quoteName('a.id') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    private function resolveOrderId($pk = null): int
    {
        if ($pk !== null) {
            return (int) $pk;
        }

        return (int) Factory::getApplication()->input->getInt('id');
    }
}