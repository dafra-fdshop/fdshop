<?php
namespace FDShop\Component\FDShop\Administrator\Service;
defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use RuntimeException;

final class OrderService implements OrderServiceInterface
{
    public function __construct(private readonly DatabaseInterface $db) {}

    public function addItem(int $orderId, int $productId, float $quantity = 1.0): int
    {
        $this->validIds($orderId, $productId); $this->validQuantity($quantity);
        $order = $this->order($orderId); $product = $this->product($productId);
        $regular = (float) $product->sale_price;
        $discount = (int) $product->discount_active === 1 && (float) $product->discount_price > 0 ? (float) $product->discount_price : 0.0;
        $gross = $discount > 0 ? $discount : $regular;
        $tax = $this->taxRate();
        $net = $gross / (1 + $tax / 100);
        $item = (object) [
            'order_id'=>$orderId, 'product_id'=>$productId, 'product_name'=>(string)$product->product_name,
            'sku'=>(string)($product->sku ?? ''), 'gtin'=>(string)($product->gtin ?? ''),
            'manufacturer_name'=>(string)($product->manufacturer_name ?? ''), 'quantity'=>$quantity,
            'regular_price_gross'=>$regular, 'discount_price_gross'=>$discount,
            'unit_price_net'=>$this->money($net), 'unit_price_gross'=>$gross, 'tax_rate'=>$tax,
            'line_total_net'=>$this->money($net*$quantity), 'line_total_gross'=>$this->money($gross*$quantity),
            'currency'=>(string)($product->currency ?: $order->currency ?: 'EUR'), 'is_removed'=>0,
        ];
        $this->db->transactionStart();
        try {
            $this->db->insertObject('#__fdshop_order_items', $item); $id=(int)$this->db->insertid();
            $this->recalculateGrandTotal($orderId);
            $this->writeOrderHistory($orderId,'item_added','Produkt hinzugefügt',sprintf('%s (SKU: %s), Menge: %s',$item->product_name,$item->sku,$this->qty($quantity)),'order_item',$id,false);
            $this->db->transactionCommit(); return $id;
        } catch (\Throwable $e) { $this->db->transactionRollback(); throw $e; }
    }

    public function removeItem(int $orderId, int $orderItemId): void
    {
        $item=$this->item($orderId,$orderItemId,true); $this->db->transactionStart();
        try {
            $q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_order_items'))->set($this->db->quoteName('is_removed').' = 1')->where('id = '.$orderItemId)->where('order_id = '.$orderId);
            $this->db->setQuery($q)->execute(); $this->recalculateGrandTotal($orderId);
            $this->writeOrderHistory($orderId,'item_removed','Produkt entfernt',sprintf('%s (SKU: %s), Menge: %s',$item->product_name,$item->sku,$this->qty((float)$item->quantity)),'order_item',$orderItemId,false);
            $this->db->transactionCommit();
        } catch (\Throwable $e) { $this->db->transactionRollback(); throw $e; }
    }

    public function changeItemQuantity(int $orderId, int $orderItemId, float $quantity): void
    {
        $this->validQuantity($quantity); $item=$this->item($orderId,$orderItemId,true); $old=(float)$item->quantity;
        $this->db->transactionStart();
        try {
            $q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_order_items'))
                ->set('quantity = '.$this->db->quote($quantity))
                ->set('line_total_net = '.$this->db->quote($this->money((float)$item->unit_price_net*$quantity)))
                ->set('line_total_gross = '.$this->db->quote($this->money((float)$item->unit_price_gross*$quantity)))
                ->where('id = '.$orderItemId)->where('order_id = '.$orderId);
            $this->db->setQuery($q)->execute(); $this->recalculateGrandTotal($orderId);
            $this->writeOrderHistory($orderId,'quantity_changed','Menge geändert',sprintf('%s: %s → %s',$item->product_name,$this->qty($old),$this->qty($quantity)),'order_item',$orderItemId,false);
            $this->db->transactionCommit();
        } catch (\Throwable $e) { $this->db->transactionRollback(); throw $e; }
    }

    public function recalculateGrandTotal(int $orderId): float
    {
        $this->order($orderId);
        $q=$this->db->getQuery(true)->select('COALESCE(SUM(line_total_gross),0)')->from($this->db->quoteName('#__fdshop_order_items'))->where('order_id = '.$orderId)->where('is_removed = 0');
        $this->db->setQuery($q); $total=(float)$this->db->loadResult();
        $q=$this->db->getQuery(true)->select('COALESCE(SUM(total_gross),0)')->from($this->db->quoteName('#__fdshop_order_bundles'))->where('order_id = '.$orderId)->where('is_removed = 0');
        $this->db->setQuery($q); $total=$this->money($total+(float)$this->db->loadResult());
        $q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_orders'))->set('grand_total = '.$this->db->quote($total))->set('modified = '.$this->db->quote(Factory::getDate()->toSql()))->where('id = '.$orderId);
        $this->db->setQuery($q)->execute(); return $total;
    }

    public function changeStatus(int $orderId, int $newStatusId, ?string $comment = null): bool
    {
        $order=$this->order($orderId); $status=$this->status($newStatusId); $old=(int)$order->order_status_id;
        if ($old === $newStatusId) return false;
        $date=Factory::getDate()->toSql(); $user=(int)Factory::getApplication()->getIdentity()->id; $comment=trim((string)$comment);
        $this->db->transactionStart();
        try {
            $q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_orders'))->set('order_status_id = '.$newStatusId)->set('order_status = '.$this->db->quote($status->status_code))->set('modified = '.$this->db->quote($date))->where('id = '.$orderId);
            $this->db->setQuery($q)->execute();
            $statusHistory = (object) ['order_id'=>$orderId,'old_status_id'=>$old ?: null,'new_status_id'=>$newStatusId,'comment'=>$comment ?: null,'is_system_change'=>0,'changed_at'=>$date,'changed_by'=>$user];
            $this->db->insertObject('#__fdshop_order_status_history', $statusHistory);
            $text=sprintf('%s → %s',(string)($order->status_name ?: $order->order_status),$status->status_name).($comment !== '' ? ': '.$comment : '');
            $this->writeOrderHistory($orderId,'status_changed','Status geändert',$text,'order_status',$newStatusId,false);
            $this->db->transactionCommit(); return true;
        } catch (\Throwable $e) { $this->db->transactionRollback(); throw $e; }
    }

    public function setOrderState(array $orderIds, int $state): int
    {
        if (!in_array($state,[-2,1],true)) throw new InvalidArgumentException('Ungültiger Bestellzustand.');
        $count=0;
        foreach(array_unique(array_filter(array_map('intval',$orderIds))) as $id) {
            $order=$this->order($id); if ((int)$order->state === $state) continue; $this->db->transactionStart();
            try {
                $q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_orders'))->set('state = '.$state)->set('modified = '.$this->db->quote(Factory::getDate()->toSql()))->where('id = '.$id);
                $this->db->setQuery($q)->execute();
                $this->writeOrderHistory($id,$state===-2?'order_trashed':'order_restored',$state===-2?'Bestellung in Papierkorb verschoben':'Bestellung wiederhergestellt',null,'order',$id,false);
                $this->db->transactionCommit(); $count++;
            } catch (\Throwable $e) { $this->db->transactionRollback(); throw $e; }
        }
        return $count;
    }

    public function writeOrderHistory(int $orderId,string $eventType,string $eventTitle,?string $eventText=null,?string $referenceType=null,?int $referenceId=null,bool $isSystemEvent=true): int
    {
        $history = (object) ['order_id'=>$orderId,'event_type'=>$eventType,'event_title'=>$eventTitle,'event_text'=>$eventText,'reference_type'=>$referenceType,'reference_id'=>$referenceId,'is_system_event'=>$isSystemEvent?1:0,'created'=>Factory::getDate()->toSql(),'created_by'=>(int)Factory::getApplication()->getIdentity()->id];
        $this->db->insertObject('#__fdshop_order_history', $history);
        return (int)$this->db->insertid();
    }

    private function product(int $id): object
    {
        $q=$this->db->getQuery(true)->select(['p.id','p.product_name','p.sale_price','p.discount_price','p.discount_active','p.currency','d.sku','d.gtin','m.manufacturer_name'])
            ->from($this->db->quoteName('#__fdshop_products','p'))->join('LEFT',$this->db->quoteName('#__fdshop_products_details','d').' ON d.product_id = p.id')->join('LEFT',$this->db->quoteName('#__fdshop_manufacturers','m').' ON m.id = p.manufacturer_id')->where('p.id = '.$id)->where('p.is_deleted = 0');
        $this->db->setQuery($q); $row=$this->db->loadObject(); if(!$row) throw new RuntimeException('Das Produkt ist nicht verfügbar oder befindet sich im Papierkorb.'); return $row;
    }
    private function order(int $id): object
    {
        if($id<=0) throw new InvalidArgumentException('Ungültige Bestellung.');
        $q=$this->db->getQuery(true)->select(['o.*','s.status_name'])->from($this->db->quoteName('#__fdshop_orders','o'))->join('LEFT',$this->db->quoteName('#__fdshop_order_statuses','s').' ON s.id = o.order_status_id')->where('o.id = '.$id);
        $this->db->setQuery($q); $row=$this->db->loadObject(); if(!$row) throw new RuntimeException('Die Bestellung wurde nicht gefunden.'); return $row;
    }
    private function item(int $orderId,int $id,bool $active): object
    {
        $this->validIds($orderId,$id); $q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_order_items'))->where('id = '.$id)->where('order_id = '.$orderId); if($active)$q->where('is_removed = 0');
        $this->db->setQuery($q); $row=$this->db->loadObject(); if(!$row) throw new RuntimeException('Die aktive Bestellposition wurde nicht gefunden.'); return $row;
    }
    private function status(int $id): object
    {
        if($id<=0) throw new InvalidArgumentException('Ungültiger Bestellstatus.'); $q=$this->db->getQuery(true)->select(['id','status_code','status_name'])->from($this->db->quoteName('#__fdshop_order_statuses'))->where('id = '.$id); $this->db->setQuery($q); $row=$this->db->loadObject(); if(!$row) throw new RuntimeException('Der Bestellstatus wurde nicht gefunden.'); return $row;
    }
    private function taxRate(): float { $q=$this->db->getQuery(true)->select('general_vat_rate')->from($this->db->quoteName('#__fdshop_config'))->where('id = 1'); $this->db->setQuery($q); return (float)$this->db->loadResult(); }
    private function validIds(int ...$ids): void { foreach($ids as $id) if($id<=0) throw new InvalidArgumentException('Ungültige Datensatz-ID.'); }
    private function validQuantity(float $q): void { if($q<=0) throw new InvalidArgumentException('Die Menge muss größer als 0 sein.'); }
    private function money(float $v): float { return round($v,4); }
    private function qty(float $v): string { return rtrim(rtrim(number_format($v,3,'.',''),'0'),'.'); }
}
