<?php
namespace FDShop\Component\FDShop\Administrator\Service;
defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use RuntimeException;

final class OrderService implements OrderServiceInterface
{
    public function __construct(private readonly DatabaseInterface $db, private readonly OrderNotificationService $notifications, private readonly ProductServiceInterface $products) {}

    public function saveDraft(int $orderId, array $items, array $newItems, int $shipmentId, string $expectedModified): bool
    {
        $this->validIds($orderId); $expectedModified=trim($expectedModified);
        $this->db->transactionStart();
        try {
            $order=$this->lockedOrder($orderId);
            $revision=(string)($order->modified ?: $order->created);
            if($expectedModified==='' || !hash_equals($revision,$expectedModified))throw new RuntimeException('Die Bestellung wurde zwischenzeitlich geändert. Bitte neu laden und erneut bearbeiten.');
            $existing=$this->lockedItems($orderId);$submitted=[];$details=[];
            foreach($existing as $item){if((int)$item->is_removed===1)continue;$id=(int)$item->id;if(!isset($items[$id])||!is_array($items[$id]))throw new RuntimeException('Der Bestellentwurf ist unvollständig. Bitte neu laden.');$quantity=(float)($items[$id]['quantity']??0);$this->validQuantity($quantity);$removed=(int)($items[$id]['removed']??0)===1;$submitted[$id]=['row'=>$item,'quantity'=>$quantity,'removed'=>$removed];if($removed)$details[]=sprintf('Produkt %s entfernt',(string)$item->product_name);elseif(abs($quantity-(float)$item->quantity)>0.0001)$details[]=sprintf('Menge %s: %s → %s',(string)$item->product_name,$this->qty((float)$item->quantity),$this->qty($quantity));}
            $additions=[];foreach($newItems as $new){if(!is_array($new))continue;$productId=(int)($new['product_id']??0);$quantity=(float)($new['quantity']??0);if($productId<=0&&$quantity<=0)continue;$this->validIds($productId);$this->validQuantity($quantity);$product=$this->lockedProduct($productId);$additions[]=['product'=>$product,'quantity'=>$quantity];$details[]=sprintf('Produkt %s hinzugefügt, Menge %s',(string)$product->product_name,$this->qty($quantity));}
            $shipmentChanged=$shipmentId!==(int)$order->shipment_id;$shipment=$this->shipmentForDraft($shipmentId,$order);if($shipmentChanged)$details[]=sprintf('Abholung/Versand: %s → %s',(string)$order->shipment_name,(string)$shipment->shipment_name);
            $oldDemand=$this->allocationDemand($orderId);$targetDemand=$this->bundleDemand($orderId);
            foreach($submitted as $entry){if($entry['removed'])continue;$row=$entry['row'];$targetDemand[(int)$row->product_id]=($targetDemand[(int)$row->product_id]??0)+$entry['quantity']*max(1,(int)$row->unit_quantity_snapshot);}
            foreach($additions as $entry){$id=(int)$entry['product']->id;$targetDemand[$id]=($targetDemand[$id]??0)+$entry['quantity'];}ksort($targetDemand);
            $demandChanged=$this->demandChanged($oldDemand,$targetDemand);
            $stockState=(string)($order->stock_state??'unknown');
            if($demandChanged&&$stockState==='deducted')throw new RuntimeException('Bereits endgültig abgezogene Bestände können nicht sicher automatisch geändert werden.');
            if($demandChanged&&$stockState==='unknown')throw new RuntimeException('Die Lagerwirkung dieser Legacy-Bestellung ist unbekannt; Positionsänderungen wurden sicher blockiert.');
            if($details===[])return $this->rollbackFalse();
            $this->applyDemandDelta($orderId,$stockState,$oldDemand,$targetDemand);
            if($stockState==='reserved'&&$demandChanged)$this->products->recalculateStockStatus($this->changedDemandProductIds($oldDemand,$targetDemand));
            foreach($submitted as $id=>$entry){$row=$entry['row'];$quantity=$entry['quantity'];$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_order_items'))->set('quantity='.$this->db->quote($quantity))->set('physical_quantity='.$this->db->quote($quantity*max(1,(int)$row->unit_quantity_snapshot)))->set('line_total_net='.$this->db->quote($this->money((float)$row->unit_price_net*$quantity)))->set('line_total_gross='.$this->db->quote($this->money((float)$row->unit_price_gross*$quantity)))->set('is_removed='.($entry['removed']?1:0))->where('id='.(int)$id)->where('order_id='.$orderId);$this->db->setQuery($q)->execute();}
            $tax=$this->taxRate();foreach($additions as $entry){$p=$entry['product'];$quantity=$entry['quantity'];$regular=(float)$p->sale_price;$discount=(int)$p->discount_active===1&&(float)$p->discount_price>0?(float)$p->discount_price:0.0;$gross=$discount>0?$discount:$regular;$net=$gross/(1+$tax/100);$document=$this->documentSnapshot((int)$p->id);$row=(object)['order_id'=>$orderId,'product_id'=>(int)$p->id,'product_name'=>(string)$p->product_name,'sku'=>(string)$p->sku,'gtin'=>(string)$p->gtin,'manufacturer_name'=>(string)$p->manufacturer_name,'quantity'=>$quantity,'unit_variant'=>'piece','unit_type_snapshot'=>'Stück','unit_quantity_snapshot'=>1,'physical_quantity'=>$quantity,'regular_price_gross'=>$regular,'discount_price_gross'=>$discount,'unit_price_net'=>$this->money($net),'unit_price_gross'=>$gross,'tax_rate'=>$tax,'line_total_net'=>$this->money($net*$quantity),'line_total_gross'=>$this->money($gross*$quantity),'currency'=>(string)($p->currency?:$order->currency?:'EUR'),'is_removed'=>0,'document_image_path'=>$document['image'],'packing_group'=>$document['group']];$this->db->insertObject('#__fdshop_order_items',$row);}
            $subtotal=$this->draftSubtotal($orderId);$coupon=(float)$order->coupon_discount;if($coupon>$subtotal+0.0001)throw new RuntimeException('Der historische Gutscheinabzug übersteigt die neue Produktsumme; diese Änderung wurde sicher blockiert.');$grand=$this->money($subtotal-$coupon+(float)$shipment->shipment_price+(float)$order->payment_fee);$date=Factory::getDate()->toSql();
            $q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_orders'))->set('shipment_id='.(int)$shipment->id)->set('shipment_name='.$this->db->quote((string)$shipment->shipment_name))->set('shipment_fee='.$this->db->quote((float)$shipment->shipment_price))->set('subtotal='.$this->db->quote($subtotal))->set('grand_total='.$this->db->quote($grand))->set('modified='.$this->db->quote($date))->where('id='.$orderId);$this->db->setQuery($q)->execute();
            $details[]=sprintf('Gesamtbetrag: %.2f → %.2f',(float)$order->grand_total,$grand);$changeId=$this->writeOrderHistory($orderId,'order_changed','Bestellung geändert',implode("\n",$details),'order',$orderId,false);
            $this->db->transactionCommit();
            $warnings=$this->notifications->sendOrderChanged($orderId,$changeId);if($warnings!==[]){$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_orders'))->set('mail_warning='.$this->db->quote(implode(' ',$warnings)))->where('id='.$orderId);$this->db->setQuery($q)->execute();}
            return true;
        } catch (\Throwable $e) {$this->db->transactionRollback();throw $e;}
    }

    public function addItem(int $orderId, int $productId, float $quantity = 1.0): int
    {
        $this->validIds($orderId, $productId); $this->validQuantity($quantity);
        $order = $this->order($orderId); $this->assertItemsEditable($order); $product = $this->product($productId);
        $regular = (float) $product->sale_price;
        $discount = (int) $product->discount_active === 1 && (float) $product->discount_price > 0 ? (float) $product->discount_price : 0.0;
        $gross = $discount > 0 ? $discount : $regular;
        $tax = $this->taxRate();
        $net = $gross / (1 + $tax / 100);
        $document=$this->documentSnapshot($productId);
        $item = (object) [
            'order_id'=>$orderId, 'product_id'=>$productId, 'product_name'=>(string)$product->product_name,
            'sku'=>(string)($product->sku ?? ''), 'gtin'=>(string)($product->gtin ?? ''),
            'manufacturer_name'=>(string)($product->manufacturer_name ?? ''), 'quantity'=>$quantity,
            'regular_price_gross'=>$regular, 'discount_price_gross'=>$discount,
            'unit_price_net'=>$this->money($net), 'unit_price_gross'=>$gross, 'tax_rate'=>$tax,
            'line_total_net'=>$this->money($net*$quantity), 'line_total_gross'=>$this->money($gross*$quantity),
            'currency'=>(string)($product->currency ?: $order->currency ?: 'EUR'), 'is_removed'=>0,
            'document_image_path'=>$document['image'], 'packing_group'=>$document['group'],
        ];
        $this->db->transactionStart();
        try {
            $this->db->insertObject('#__fdshop_order_items', $item); $id=(int)$this->db->insertid();
            $this->recalculateGrandTotal($orderId);
            $this->writeOrderHistory($orderId,'item_added','Produkt hinzugefügt',sprintf('%s (SKU: %s), Menge: %s',$item->product_name,$item->sku,$this->qty($quantity)),'order_item',$id,false);
            $this->db->transactionCommit(); return $id;
        } catch (\Throwable $e) { $this->db->transactionRollback(); throw $e; }
    }

    private function documentSnapshot(int $productId): array
    {
        $q=$this->db->getQuery(true)->select('document_special_category_id')->from($this->db->quoteName('#__fdshop_config'))->where('id=1');$this->db->setQuery($q);$special=(int)$this->db->loadResult();
        $q=$this->db->getQuery(true)->select('COALESCE(path_invoice,path_small,path_standard,path_mobile,\'\')')->from($this->db->quoteName('#__fdshop_media'))->where('product_id='.$productId)->where('media_type='.$this->db->quote('image'))->order('is_primary DESC, ordering ASC, id ASC');$this->db->setQuery($q,0,1);$image=(string)$this->db->loadResult();
        $group=1;if($special>0){$q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__fdshop_product_category_map'))->where('product_id='.$productId)->where('category_id='.$special);$this->db->setQuery($q);$group=(int)$this->db->loadResult()>0?2:1;}
        return ['image'=>$image?:null,'group'=>$group];
    }

    public function removeItem(int $orderId, int $orderItemId): void
    {
        $this->assertItemsEditable($this->order($orderId)); $item=$this->item($orderId,$orderItemId,true); $this->db->transactionStart();
        try {
            $q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_order_items'))->set($this->db->quoteName('is_removed').' = 1')->where('id = '.$orderItemId)->where('order_id = '.$orderId);
            $this->db->setQuery($q)->execute(); $this->recalculateGrandTotal($orderId);
            $this->writeOrderHistory($orderId,'item_removed','Produkt entfernt',sprintf('%s (SKU: %s), Menge: %s',$item->product_name,$item->sku,$this->qty((float)$item->quantity)),'order_item',$orderItemId,false);
            $this->db->transactionCommit();
        } catch (\Throwable $e) { $this->db->transactionRollback(); throw $e; }
    }

    public function changeItemQuantity(int $orderId, int $orderItemId, float $quantity): void
    {
        $this->validQuantity($quantity); $this->assertItemsEditable($this->order($orderId)); $item=$this->item($orderId,$orderItemId,true); $old=(float)$item->quantity;
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
            $this->db->setQuery('SELECT id FROM '.$this->db->quoteName('#__fdshop_orders').' WHERE id='.(int)$orderId.' FOR UPDATE')->loadResult();
            $this->transitionStock($orderId,(string)($order->stock_state??'unknown'),(string)$status->stock_action,$date);
            $q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_orders'))->set('order_status_id = '.$newStatusId)->set('order_status = '.$this->db->quote($status->status_code))->set('modified = '.$this->db->quote($date))->where('id = '.$orderId);
            $this->db->setQuery($q)->execute();
            $statusHistory = (object) ['order_id'=>$orderId,'old_status_id'=>$old ?: null,'new_status_id'=>$newStatusId,'comment'=>$comment ?: null,'is_system_change'=>0,'changed_at'=>$date,'changed_by'=>$user];
            $this->db->insertObject('#__fdshop_order_status_history', $statusHistory);
            $text=sprintf('%s → %s',(string)($order->status_name ?: $order->order_status),$status->status_name).($comment !== '' ? ': '.$comment : '');
            $this->writeOrderHistory($orderId,'status_changed','Status geändert',$text,'order_status',$newStatusId,false);
            $this->db->transactionCommit();
            $warnings=$this->notifications->sendForStatus($orderId,$newStatusId);
            if($warnings!==[]){$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_orders'))->set('mail_warning='.$this->db->quote(implode(' ',$warnings)))->where('id='.$orderId);$this->db->setQuery($q)->execute();}
            return true;
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

    private function lockedOrder(int $id): object
    {
        if($id<=0)throw new InvalidArgumentException('Ungültige Bestellung.');$this->db->setQuery('SELECT * FROM '.$this->db->quoteName('#__fdshop_orders').' WHERE id='.(int)$id.' FOR UPDATE');$row=$this->db->loadObject();if(!$row)throw new RuntimeException('Die Bestellung wurde nicht gefunden.');return $row;
    }
    private function lockedItems(int $orderId): array
    {
        $this->db->setQuery('SELECT * FROM '.$this->db->quoteName('#__fdshop_order_items').' WHERE order_id='.(int)$orderId.' ORDER BY id ASC FOR UPDATE');return (array)$this->db->loadObjectList();
    }
    private function lockedProduct(int $id): object
    {
        $q=$this->db->getQuery(true)->select(['p.id','p.product_name','p.sale_price','p.discount_price','p.discount_active','p.currency','d.sku','d.gtin','d.stock_quantity','d.reserved_quantity','m.manufacturer_name'])->from($this->db->quoteName('#__fdshop_products','p'))->join('INNER',$this->db->quoteName('#__fdshop_products_details','d').' ON d.product_id=p.id')->join('LEFT',$this->db->quoteName('#__fdshop_manufacturers','m').' ON m.id=p.manufacturer_id')->where('p.id='.(int)$id)->where('p.is_deleted=0')->where('p.is_active=1');$this->db->setQuery((string)$q.' FOR UPDATE');$row=$this->db->loadObject();if(!$row)throw new RuntimeException('Das aktive Produkt wurde nicht gefunden.');return $row;
    }
    private function shipmentForDraft(int $id, object $order): object
    {
        if($id===(int)$order->shipment_id)return (object)['id'=>(int)$order->shipment_id,'shipment_name'=>(string)$order->shipment_name,'shipment_price'=>(float)$order->shipment_fee];
        if($id<=0)throw new InvalidArgumentException('Bitte wählen Sie eine gültige Abhol-/Versandart.');$q=$this->db->getQuery(true)->select(['id','shipment_name','shipment_price'])->from($this->db->quoteName('#__fdshop_shipments'))->where('id='.(int)$id)->where('published=1');$this->db->setQuery($q);$row=$this->db->loadObject();if(!$row)throw new RuntimeException('Die gewählte Abhol-/Versandart ist nicht aktiv verfügbar.');return $row;
    }
    private function allocationDemand(int $orderId): array
    {
        $q=$this->db->getQuery(true)->select(['product_id','physical_quantity'])->from($this->db->quoteName('#__fdshop_order_stock_allocations'))->where('order_id='.(int)$orderId)->order('product_id ASC');$this->db->setQuery((string)$q.' FOR UPDATE');$out=[];foreach((array)$this->db->loadObjectList() as $row)$out[(int)$row->product_id]=(float)$row->physical_quantity;ksort($out);return $out;
    }
    private function bundleDemand(int $orderId): array
    {
        $q=$this->db->getQuery(true)->select(['i.product_id','SUM(i.quantity) AS physical_quantity'])->from($this->db->quoteName('#__fdshop_order_bundle_items','i'))->join('INNER',$this->db->quoteName('#__fdshop_order_bundles','b').' ON b.id=i.order_bundle_id')->where('b.order_id='.(int)$orderId)->where('b.is_removed=0')->where('i.is_removed=0')->group('i.product_id');$this->db->setQuery($q);$out=[];foreach((array)$this->db->loadObjectList() as $row)$out[(int)$row->product_id]=(float)$row->physical_quantity;return $out;
    }
    private function demandChanged(array $old,array $new): bool
    {
        foreach(array_unique(array_merge(array_keys($old),array_keys($new))) as $id)if(abs(($old[$id]??0)-($new[$id]??0))>0.0001)return true;return false;
    }
    private function changedDemandProductIds(array $old,array $new): array
    {
        $changed=[];foreach(array_unique(array_merge(array_keys($old),array_keys($new))) as $id)if(abs(($old[$id]??0)-($new[$id]??0))>0.0001)$changed[]=(int)$id;return $changed;
    }
    private function applyDemandDelta(int $orderId,string $state,array $old,array $new): void
    {
        foreach(array_unique(array_merge(array_keys($old),array_keys($new))) as $productId){$before=(float)($old[$productId]??0);$after=(float)($new[$productId]??0);$delta=$after-$before;$stock=null;$needsStock=$after>0.0001||($state==='reserved'&&abs($delta)>0.0001);if($needsStock&&in_array($state,['reserved','available','none'],true)){$q=$this->db->getQuery(true)->select(['product_id','stock_quantity','reserved_quantity'])->from($this->db->quoteName('#__fdshop_products_details'))->where('product_id='.(int)$productId);$this->db->setQuery((string)$q.' FOR UPDATE');$stock=$this->db->loadObject();if(!$stock)throw new RuntimeException('Ein lagerrelevantes Produkt wurde nicht gefunden.');$available=(float)$stock->stock_quantity-(float)$stock->reserved_quantity;if($state==='reserved'&&$delta>0&&$available+0.0001<$delta)throw new RuntimeException('Der verfügbare Bestand reicht für die gesamte Bestelländerung nicht aus.');if(in_array($state,['available','none'],true)&&$available+0.0001<$after)throw new RuntimeException('Der verfügbare Bestand reicht für die gesamte Bestelländerung nicht aus.');}
            if(abs($delta)>0.0001&&$state==='reserved'){if((float)$stock->reserved_quantity+$delta< -0.0001)throw new RuntimeException('Die gespeicherte Reservierung ist inkonsistent; die Änderung wurde sicher blockiert.');$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_products_details'))->set('reserved_quantity=reserved_quantity+'.$this->db->quote($delta))->where('product_id='.(int)$productId);$this->db->setQuery($q)->execute();}
            if($after<=0.0001){$q=$this->db->getQuery(true)->delete($this->db->quoteName('#__fdshop_order_stock_allocations'))->where('order_id='.(int)$orderId)->where('product_id='.(int)$productId);$this->db->setQuery($q)->execute();continue;}
            $q=$this->db->getQuery(true)->select('id')->from($this->db->quoteName('#__fdshop_order_stock_allocations'))->where('order_id='.(int)$orderId)->where('product_id='.(int)$productId);$this->db->setQuery($q);$id=(int)$this->db->loadResult();$now=Factory::getDate()->toSql();if($id){$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_order_stock_allocations'))->set('physical_quantity='.$this->db->quote($after))->set('stock_state='.$this->db->quote($state))->set('modified='.$this->db->quote($now))->where('id='.$id);$this->db->setQuery($q)->execute();}else{$row=(object)['order_id'=>$orderId,'product_id'=>(int)$productId,'physical_quantity'=>$after,'stock_state'=>$state,'created'=>$now];$this->db->insertObject('#__fdshop_order_stock_allocations',$row);}}
    }
    private function draftSubtotal(int $orderId): float
    {
        $q=$this->db->getQuery(true)->select('COALESCE(SUM(line_total_gross),0)')->from($this->db->quoteName('#__fdshop_order_items'))->where('order_id='.(int)$orderId)->where('is_removed=0');$this->db->setQuery($q);$subtotal=(float)$this->db->loadResult();$q=$this->db->getQuery(true)->select('COALESCE(SUM(total_gross),0)')->from($this->db->quoteName('#__fdshop_order_bundles'))->where('order_id='.(int)$orderId)->where('is_removed=0');$this->db->setQuery($q);return $this->money($subtotal+(float)$this->db->loadResult());
    }
    private function rollbackFalse(): bool {$this->db->transactionRollback();return false;}

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
        if($id<=0) throw new InvalidArgumentException('Ungültiger Bestellstatus.'); $q=$this->db->getQuery(true)->select(['id','status_code','status_name','stock_action','notify_seller','notify_buyer','create_invoice','seller_email_mode','seller_email_address','buyer_email_mode'])->from($this->db->quoteName('#__fdshop_order_statuses'))->where('id = '.$id)->where('is_active = 1'); $this->db->setQuery($q); $row=$this->db->loadObject(); if(!$row) throw new RuntimeException('Der aktive Bestellstatus wurde nicht gefunden.'); return $row;
    }
    private function assertItemsEditable(object $order): void { if(in_array((string)($order->stock_state??''),['reserved','deducted'],true)) throw new RuntimeException('Lagerrelevante Positionen einer reservierten oder abgezogenen Bestellung dürfen in V1 nicht verändert werden.'); }
    private function transitionStock(int $orderId,string $current,string $action,string $date): void
    {
        if($current==='unknown') { if($action!=='none') throw new RuntimeException('Die Lagerwirkung dieser Legacy-Bestellung ist unbekannt; der Statuswechsel wurde sicher blockiert.'); return; }
        if($action==='none') return;
        $target=match($action){'reserve'=>'reserved','deduct'=>'deducted','available'=>'available',default=>throw new RuntimeException('Ungültige Lageraktion im Bestellstatus.')};
        if($current===$target)return;
        if($current==='deducted' && $target!=='deducted')throw new RuntimeException('Bereits abgezogener Bestand kann in V1 nicht automatisch zurückgebucht werden.');
        $q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_order_stock_allocations'))->where('order_id='.(int)$orderId)->order('product_id ASC');$this->db->setQuery($q);$allocations=(array)$this->db->loadObjectList();
        if($allocations===[])throw new RuntimeException('Für diese Bestellung existiert keine sichere Lagerzuordnung.');
        $affectedProductIds=[];
        foreach($allocations as $a){$this->db->setQuery('SELECT product_id FROM '.$this->db->quoteName('#__fdshop_products_details').' WHERE product_id='.(int)$a->product_id.' FOR UPDATE')->loadResult();$qty=(float)$a->physical_quantity;
            if($target==='reserved' && $current==='available'){$set='reserved_quantity=reserved_quantity+'.$qty;}
            elseif($target==='available' && $current==='reserved'){$set='reserved_quantity=GREATEST(0,reserved_quantity-'.$qty.')';}
            elseif($target==='deducted' && $current==='reserved'){$set='reserved_quantity=GREATEST(0,reserved_quantity-'.$qty.'), stock_quantity=stock_quantity-'.$qty;}
            elseif($target==='deducted' && $current==='available'){$set='stock_quantity=stock_quantity-'.$qty;}
            else throw new RuntimeException('Dieser Lagerzustandswechsel ist in V1 nicht sicher automatisierbar.');
            $q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_products_details'))->set($set)->where('product_id='.(int)$a->product_id);$this->db->setQuery($q)->execute();
            $q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_order_stock_allocations'))->set('stock_state='.$this->db->quote($target))->set('modified='.$this->db->quote($date))->where('id='.(int)$a->id);$this->db->setQuery($q)->execute();$affectedProductIds[]=(int)$a->product_id;}
        $this->products->recalculateStockStatus($affectedProductIds);
        $q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_orders'))->set('stock_state='.$this->db->quote($target))->where('id='.(int)$orderId);$this->db->setQuery($q)->execute();
        $this->writeOrderHistory($orderId,'stock_changed','Lagerwirkung geändert',$current.' → '.$target,'stock',null,true);
    }
    private function taxRate(): float { $q=$this->db->getQuery(true)->select('general_vat_rate')->from($this->db->quoteName('#__fdshop_config'))->where('id = 1'); $this->db->setQuery($q); return (float)$this->db->loadResult(); }
    private function validIds(int ...$ids): void { foreach($ids as $id) if($id<=0) throw new InvalidArgumentException('Ungültige Datensatz-ID.'); }
    private function validQuantity(float $q): void { if($q<=0) throw new InvalidArgumentException('Die Menge muss größer als 0 sein.'); }
    private function money(float $v): float { return round($v,4); }
    private function qty(float $v): string { return rtrim(rtrim(number_format($v,3,'.',''),'0'),'.'); }
}
