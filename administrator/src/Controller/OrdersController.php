<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;

class OrdersController extends AdminController
{
    protected $text_prefix = 'COM_FDSHOP_ORDERS';

    protected $default_view = 'orders';

    public function getModel($name = 'Orders', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function save($key = null, $urlVar = null): bool
    {
        $cid           = $this->input->post->get('cid', [], 'array');
        $newStatusId   = $this->input->post->getInt('order_status_id', 0);

        $cid = array_values(array_filter(array_map('intval', $cid)));

        if (empty($cid)) {
            $this->setMessage('Keine Bestellungen markiert.', 'warning');
            $this->setRedirect('index.php?option=com_fdshop&view=orders');

            return false;
        }

        if ($newStatusId <= 0) {
            $this->setMessage('Kein Bestellstatus ausgewählt.', 'warning');
            $this->setRedirect('index.php?option=com_fdshop&view=orders');

            return false;
        }

        $db   = Factory::getContainer()->get('DatabaseDriver');
        $user = Factory::getApplication()->getIdentity();
        $date = Factory::getDate()->toSql();

        $statusQuery = $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('status_code'),
            ])
            ->from($db->quoteName('#__fdshop_order_statuses'))
            ->where($db->quoteName('id') . ' = ' . (int) $newStatusId);

        $db->setQuery($statusQuery);
        $newStatus = $db->loadObject();

        if (!$newStatus) {
            $this->setMessage('Ungültiger Bestellstatus.', 'error');
            $this->setRedirect('index.php?option=com_fdshop&view=orders');

            return false;
        }

        foreach ($cid as $orderId) {
            $oldStatusQuery = $db->getQuery(true)
                ->select($db->quoteName('order_status_id'))
                ->from($db->quoteName('#__fdshop_orders'))
                ->where($db->quoteName('id') . ' = ' . (int) $orderId);

            $db->setQuery($oldStatusQuery);
            $oldStatusId = (int) $db->loadResult();

            if ($oldStatusId <= 0) {
                continue;
            }

            $updateQuery = $db->getQuery(true)
                ->update($db->quoteName('#__fdshop_orders'))
                ->set($db->quoteName('order_status_id') . ' = ' . (int) $newStatusId)
                ->set($db->quoteName('order_status') . ' = ' . $db->quote((string) $newStatus->status_code))
                ->set($db->quoteName('modified') . ' = ' . $db->quote($date))
                ->where($db->quoteName('id') . ' = ' . (int) $orderId);

            $db->setQuery($updateQuery);
            $db->execute();

            if ($oldStatusId !== $newStatusId) {
                $historyQuery = $db->getQuery(true)
                    ->insert($db->quoteName('#__fdshop_order_status_history'))
                    ->columns([
                        $db->quoteName('order_id'),
                        $db->quoteName('old_status_id'),
                        $db->quoteName('new_status_id'),
                        $db->quoteName('comment'),
                        $db->quoteName('is_system_change'),
                        $db->quoteName('changed_at'),
                        $db->quoteName('changed_by'),
                    ])
                    ->values(
                        implode(
                            ',',
                            [
                                (int) $orderId,
                                (int) $oldStatusId,
                                (int) $newStatusId,
                                $db->quote('Bulk-Statusänderung aus der Bestellliste'),
                                0,
                                $db->quote($date),
                                (int) $user->id,
                            ]
                        )
                    );

                $db->setQuery($historyQuery);
                $db->execute();
            }
        }

        $this->setMessage('Markierte Bestellungen gespeichert.');
        $this->setRedirect('index.php?option=com_fdshop&view=orders');

        return true;
    }

    public function trash(): bool
    {
        $cid = $this->input->post->get('cid', [], 'array');
        $cid = array_values(array_filter(array_map('intval', $cid)));

        if (empty($cid)) {
            $this->setMessage('Keine Bestellungen markiert.', 'warning');
            $this->setRedirect('index.php?option=com_fdshop&view=orders');

            return false;
        }

        $confirmed = $this->input->post->getInt('confirm_trash', 0);

        if ($confirmed !== 1) {
            $this->setMessage('ACHTUNG sind sie sicher, dass sie die Bestellung in den Papierkorb verschieben wollen!', 'warning');
            $this->setRedirect('index.php?option=com_fdshop&view=orders');

            return false;
        }

        $db   = Factory::getContainer()->get('DatabaseDriver');
        $date = Factory::getDate()->toSql();

        foreach ($cid as $orderId) {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__fdshop_orders'))
                ->set($db->quoteName('state') . ' = -2')
                ->set($db->quoteName('modified') . ' = ' . $db->quote($date))
                ->where($db->quoteName('id') . ' = ' . (int) $orderId);

            $db->setQuery($query);
            $db->execute();
        }

        $this->setMessage('Markierte Bestellungen wurden in den Papierkorb verschoben.');
        $this->setRedirect('index.php?option=com_fdshop&view=orders');

        return true;
    }
}