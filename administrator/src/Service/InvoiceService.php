<?php
namespace FDShop\Component\FDShop\Administrator\Service;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class InvoiceService
{
    public function __construct(private readonly DatabaseInterface $db, private readonly OrderDocumentService $documents) {}

    public function issue(int $orderId): array
    {
        $now = Factory::getDate('now', 'UTC')->toSql();
        $period = Factory::getDate($now, 'UTC')->format('ym', false);
        $lock = 'fdshop_invoice_' . $period;
        $this->db->setQuery('SELECT GET_LOCK(' . $this->db->quote($lock) . ',10)');
        if ((int) $this->db->loadResult() !== 1) throw new \RuntimeException('Der Rechnungsnummernkreis ist derzeit belegt.');
        try {
            $this->db->transactionStart();
            try {
                $order = $this->lockedOrder($orderId);
                if (!$order) throw new \RuntimeException('Bestellung nicht gefunden.');
                if (empty($order->invoice_number)) {
                    $status = $this->status((int) $order->order_status_id);
                    if (!$status || (int) $status->create_invoice !== 1) throw new \RuntimeException('Die Bestellung ist noch nicht bezahlt.');
                    $number = $this->nextNumber($period, $now);
                    $invoice = 'RE-' . $period . sprintf('%04d', $number);
                    $q = $this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_orders'))
                        ->set($this->db->quoteName('invoice_number') . '=' . $this->db->quote($invoice))
                        ->set($this->db->quoteName('invoice_created_at') . '=' . $this->db->quote($now))
                        ->where($this->db->quoteName('id') . '=' . $orderId);
                    $this->db->setQuery($q)->execute();
                }
                $this->db->transactionCommit();
            } catch (\Throwable $e) {
                $this->db->transactionRollback();
                throw $e;
            }
        } finally {
            $this->db->setQuery('SELECT RELEASE_LOCK(' . $this->db->quote($lock) . ')')->loadResult();
        }
        return $this->lockedDocument($orderId, false);
    }

    public function cancel(int $orderId): ?array
    {
        $this->db->transactionStart();
        try {
            $order = $this->lockedOrder($orderId);
            if (!$order) throw new \RuntimeException('Bestellung nicht gefunden.');
            if (empty($order->invoice_number)) { $this->db->transactionCommit(); return null; }
            if (empty($order->invoice_cancelled_at)) {
                $now = Factory::getDate('now', 'UTC')->toSql();
                $q = $this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_orders'))
                    ->set($this->db->quoteName('invoice_cancelled_at') . '=' . $this->db->quote($now))
                    ->where($this->db->quoteName('id') . '=' . $orderId);
                $this->db->setQuery($q)->execute();
            }
            $this->db->transactionCommit();
        } catch (\Throwable $e) {
            $this->db->transactionRollback();
            throw $e;
        }
        return $this->lockedDocument($orderId, true);
    }

    private function lockedDocument(int $orderId, bool $cancelled): array
    {
        $this->db->transactionStart();
        try {
            if (!$this->lockedOrder($orderId)) throw new \RuntimeException('Bestellung nicht gefunden.');
            $document = $this->documents->invoiceDocument($orderId, $cancelled);
            $this->db->transactionCommit();
            return $document;
        } catch (\Throwable $e) {
            $this->db->transactionRollback();
            throw $e;
        }
    }

    private function nextNumber(string $period, string $now): int
    {
        $sql = 'INSERT IGNORE INTO ' . $this->db->quoteName('#__fdshop_invoice_sequences')
            . ' (' . $this->db->quoteName('period') . ',' . $this->db->quoteName('last_number') . ',' . $this->db->quoteName('modified') . ') VALUES ('
            . $this->db->quote($period) . ',0,' . $this->db->quote($now) . ')';
        $this->db->setQuery($sql)->execute();
        $q = $this->db->getQuery(true)->select('last_number')->from($this->db->quoteName('#__fdshop_invoice_sequences'))->where('period='.$this->db->quote($period));
        $this->db->setQuery((string)$q.' FOR UPDATE');$next=(int)$this->db->loadResult()+1;
        if ($next > 9999) throw new \RuntimeException('Der monatliche Rechnungsnummernkreis ist ausgeschöpft.');
        $q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_invoice_sequences'))->set('last_number='.$next)->set('modified='.$this->db->quote($now))->where('period='.$this->db->quote($period));$this->db->setQuery($q)->execute();
        return $next;
    }

    private function lockedOrder(int $id): ?object
    {
        $q = $this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_orders'))
            ->where($this->db->quoteName('id') . '=' . $id);
        $this->db->setQuery((string) $q . ' FOR UPDATE'); return $this->db->loadObject() ?: null;
    }

    private function status(int $id): ?object
    {
        $q = $this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_order_statuses'))->where('id=' . $id);
        $this->db->setQuery($q); return $this->db->loadObject() ?: null;
    }
}
