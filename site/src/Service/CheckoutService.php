<?php
namespace FDShop\Component\FDShop\Site\Service;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use FDShop\Component\FDShop\Administrator\Service\OrderNotificationService;
use FDShop\Component\FDShop\Administrator\Service\ProductServiceInterface;

final class CheckoutService implements CheckoutServiceInterface
{
    public function __construct(private readonly DatabaseInterface $db, private readonly CartServiceInterface $cartService, private readonly OrderNotificationService $notifications, private readonly ProductServiceInterface $products) {}

    public function createOrder(int $userId, string $sessionId, int $shipmentId, int $paymentId, string $couponCode, string $note, bool $termsAccepted, string $submissionId): array
    {
        if ($userId < 1) throw new \DomainException('Bitte melden Sie sich an oder registrieren Sie sich, um die Bestellung abzuschließen.');
        if (!preg_match('/^[0-9a-f-]{36}$/i', $submissionId)) throw new \DomainException('Die Bestellanfrage ist ungültig. Bitte laden Sie den Warenkorb neu.');
        $existing = $this->orderBySubmission($userId, $submissionId);
        if ($existing) return $this->result($existing, true);
        $note = trim(strip_tags($note));
        if (mb_strlen($note) > 2000) throw new \DomainException('Die Bemerkung darf höchstens 2000 Zeichen lang sein.');
        $this->db->transactionStart();
        try {
            $customer = $this->customerSnapshot($userId);
            $config = $this->lockOne('#__fdshop_config', 'id = 1');
            if (!$config || (int)$config->katalog_active === 1) throw new \DomainException('Bestellungen sind im Katalogmodus nicht möglich.');
            $required = (int)$config->require_terms_checkbox === 1;
            if ($required && !$termsAccepted) throw new \DomainException('Bitte bestätigen Sie die AGB und die Widerrufsbelehrung.');
            $status = $this->lockOne('#__fdshop_order_statuses', "status_code = 'ordered' AND is_active = 1");
            if (!$status) throw new \RuntimeException('Der Bestellstatus ordered ist nicht aktiv verfügbar.');
            $cart = $this->cartService->getCart($userId, $sessionId, $shipmentId, $paymentId, $couponCode);
            if ($cart['items'] === [] && ($cart['bundles'] ?? []) === []) throw new \DomainException('Der Warenkorb ist leer.');
            if (!$cart['shipment'] || !$cart['payment']) throw new \DomainException('Versand- und Zahlungsart müssen ausgewählt sein.');
            if ($couponCode !== '' && ($cart['coupon_code'] ?? '') === '') throw new \DomainException('Der Gutschein ist nicht mehr gültig. Bitte prüfen Sie den Warenkorb erneut.');

            $demand = [];
            foreach ($cart['items'] as $item) $demand[(int)$item->product_id] = ($demand[(int)$item->product_id] ?? 0) + (float)$item->physical_quantity;
            foreach (($cart['bundles'] ?? []) as $bundle) foreach ($bundle->items as $item) $demand[(int)$item->product_id] = ($demand[(int)$item->product_id] ?? 0) + (float)$item->quantity;
            ksort($demand);
            foreach ($demand as $productId => $quantity) {
                $stock = $this->lockOne('#__fdshop_products_details', 'product_id = '.(int)$productId);
                if (!$stock || ((float)$stock->stock_quantity - (float)$stock->reserved_quantity) + 0.0001 < $quantity) throw new \DomainException('Mindestens ein Produkt ist nicht mehr in ausreichender Menge verfügbar.');
            }
            if ($couponCode !== '') $this->lockOne('#__fdshop_coupons', 'coupon_code = '.$this->db->quote(strtoupper(trim($couponCode))));

            $date = Factory::getDate()->toSql();
            $row = (object)[
                'order_number'=>$this->orderNumber(), 'user_id'=>$userId, 'buyer_group_id'=>(int)($cart['items'][0]->buyer_group_id ?? 0),
                'payment_method_id'=>(int)$cart['payment']->id, 'shipment_id'=>(int)$cart['shipment']->id,
                'order_status'=>'ordered', 'order_status_id'=>(int)$status->id, 'state'=>1, 'currency'=>(string)$cart['currency'],
                'grand_total'=>(float)$cart['total'], 'has_bundle'=>empty($cart['bundles'])?0:1,
                'customer_name'=>$customer['name'], 'customer_email'=>$customer['email'],
                'customer_first_name'=>$customer['first_name'], 'customer_last_name'=>$customer['last_name'],
                'customer_company'=>$customer['company'] ?: null, 'customer_street'=>$customer['street'] ?: null,
                'customer_postal_code'=>$customer['postal_code'] ?: null, 'customer_city'=>$customer['city'] ?: null,
                'customer_country'=>$customer['country'] ?: null, 'customer_phone'=>$customer['phone'] ?: null,
                'payment_method_name'=>(string)$cart['payment']->name, 'payment_fee'=>(float)$cart['payment_fee'],
                'shipment_name'=>(string)$cart['shipment']->name, 'shipment_fee'=>(float)$cart['shipment_fee'],
                'subtotal'=>(float)$cart['subtotal'], 'coupon_code'=>(string)($cart['coupon_code']??''), 'coupon_discount'=>(float)($cart['coupon_discount']??0),
                'order_note'=>$note?:null, 'terms_required'=>$required?1:0, 'terms_accepted'=>$termsAccepted?1:0,
                'terms_accepted_at'=>$termsAccepted?$date:null, 'stock_state'=>'none', 'submission_id'=>strtolower($submissionId), 'created'=>$date,
            ];
            $this->db->insertObject('#__fdshop_orders',$row); $orderId=(int)$this->db->insertid();
            $tax=(float)$config->general_vat_rate;
            foreach ($cart['items'] as $item) {
                $document=$this->documentSnapshot((int)$item->product_id,(int)($config->document_special_category_id??0));
                $orderItem=(object)['order_id'=>$orderId,'product_id'=>(int)$item->product_id,'product_name'=>(string)$item->sales_name,'sku'=>(string)$item->sales_sku,'gtin'=>'','manufacturer_name'=>'','quantity'=>(float)$item->quantity,'unit_variant'=>(string)$item->unit_variant,'unit_type_snapshot'=>(string)$item->unit_type_snapshot,'unit_quantity_snapshot'=>(int)$item->unit_quantity_snapshot,'physical_quantity'=>(float)$item->physical_quantity,'regular_price_gross'=>(float)$item->sale_price,'discount_price_gross'=>$item->has_discount?(float)$item->unit_price:0,'unit_price_net'=>round((float)$item->unit_price/(1+$tax/100),4),'unit_price_gross'=>(float)$item->unit_price,'tax_rate'=>$tax,'line_total_net'=>round((float)$item->line_total/(1+$tax/100),4),'line_total_gross'=>(float)$item->line_total,'currency'=>(string)$item->currency,'is_removed'=>0,'document_image_path'=>$document['image'],'packing_group'=>$document['group']];
                $this->db->insertObject('#__fdshop_order_items',$orderItem);
            }
            foreach (($cart['bundles']??[]) as $bundle) {
                $head=(object)['order_id'=>$orderId,'bundle_id'=>(int)$bundle->bundle_id,'bundle_number'=>(string)$bundle->bundle_number,'bundle_name'=>(string)$bundle->bundle_name,'quantity_items'=>(float)$bundle->total_quantity,'subtotal_net'=>round((float)$bundle->subtotal_gross/(1+$tax/100),4),'subtotal_gross'=>(float)$bundle->subtotal_gross,'discount_percent'=>(float)$bundle->discount_percent,'discount_amount_net'=>round((float)$bundle->discount_amount_gross/(1+$tax/100),4),'discount_amount_gross'=>(float)$bundle->discount_amount_gross,'total_net'=>round((float)$bundle->total_gross/(1+$tax/100),4),'total_gross'=>(float)$bundle->total_gross,'is_removed'=>0,'created'=>$date];
                $this->db->insertObject('#__fdshop_order_bundles',$head); $orderBundleId=(int)$this->db->insertid();
                foreach($bundle->items as $item){$document=$this->documentSnapshot((int)$item->product_id,(int)($config->document_special_category_id??0));$bi=(object)['order_bundle_id'=>$orderBundleId,'product_id'=>(int)$item->product_id,'product_name'=>(string)$item->product_name,'sku'=>(string)$item->sku,'quantity'=>(float)$item->quantity,'regular_price_gross'=>(float)$item->unit_price_gross,'unit_price_net'=>round((float)$item->unit_price_gross/(1+$tax/100),4),'unit_price_gross'=>(float)$item->unit_price_gross,'tax_rate'=>$tax,'total_net'=>round((float)$item->total_gross/(1+$tax/100),4),'total_gross'=>(float)$item->total_gross,'currency'=>(string)$bundle->currency,'is_removed'=>0,'document_image_path'=>$document['image'],'packing_group'=>$document['group'],'created'=>$date];$this->db->insertObject('#__fdshop_order_bundle_items',$bi);}
            }
            foreach($demand as $productId=>$quantity){$allocation=(object)['order_id'=>$orderId,'product_id'=>$productId,'physical_quantity'=>$quantity,'stock_state'=>'none','created'=>$date];$this->db->insertObject('#__fdshop_order_stock_allocations',$allocation);}
            $this->applyStockAction($orderId,(string)$status->stock_action,$demand,$date);
            if (($cart['coupon_code']??'') !== '') $this->consumeCoupon($orderId,$userId,(string)$cart['coupon_code'],(float)$cart['coupon_discount'],$tax,$date);
            $statusHistory=(object)['order_id'=>$orderId,'old_status_id'=>null,'new_status_id'=>(int)$status->id,'comment'=>'Bestellung durch Kunden erzeugt','is_system_change'=>1,'changed_at'=>$date,'changed_by'=>$userId];
            $this->db->insertObject('#__fdshop_order_status_history',$statusHistory);
            $orderHistory=(object)['order_id'=>$orderId,'event_type'=>'order_created','event_title'=>'Bestellung erzeugt','event_text'=>'Checkout erfolgreich abgeschlossen','reference_type'=>'order','reference_id'=>$orderId,'is_system_event'=>1,'created'=>$date,'created_by'=>$userId];
            $this->db->insertObject('#__fdshop_order_history',$orderHistory);
            $this->deleteCart($userId,$sessionId);
            $this->db->transactionCommit();
            $warnings=$this->notifications->sendForStatus($orderId,(int)$status->id);
            if($warnings!==[]){$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_orders'))->set('mail_warning='.$this->db->quote(implode(' ',$warnings)))->where('id='.$orderId);$this->db->setQuery($q)->execute();}
            return $this->result((object)['id'=>$orderId,'order_number'=>$row->order_number,'grand_total'=>$row->grand_total,'currency'=>$row->currency],false);
        } catch (\Throwable $e) { $this->db->transactionRollback(); $existing=$this->orderBySubmission($userId,$submissionId); if($existing)return $this->result($existing,true); throw $e; }
    }

    public function getConfirmation(int $userId,string $orderNumber): ?array
    {
        if($userId<1||$orderNumber==='')return null;
        $q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_orders'))->where('user_id='.(int)$userId)->where('order_number='.$this->db->quote($orderNumber));$this->db->setQuery($q);$order=$this->db->loadObject();if(!$order)return null;
        $q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_order_items'))->where('order_id='.(int)$order->id)->where('is_removed=0')->order('id ASC');$this->db->setQuery($q);
        return ['order'=>$order,'items'=>(array)$this->db->loadObjectList()];
    }

    private function applyStockAction(int $orderId,string $action,array $demand,string $date):void
    {if(!in_array($action,['none','reserve','deduct','available'],true))throw new \RuntimeException('Ungültige Lageraktion.'); if($action==='none')return;$affected=[]; foreach($demand as $id=>$qty){if($action==='reserve'){$set='reserved_quantity = reserved_quantity + '.(float)$qty;$state='reserved';}elseif($action==='deduct'){$set='stock_quantity = stock_quantity - '.(float)$qty;$state='deducted';}else{$state='available';continue;}$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_products_details'))->set($set)->where('product_id='.(int)$id);$this->db->setQuery($q)->execute();$affected[]=(int)$id;$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_order_stock_allocations'))->set('stock_state='.$this->db->quote($state))->set('modified='.$this->db->quote($date))->where('order_id='.$orderId)->where('product_id='.(int)$id);$this->db->setQuery($q)->execute();}if($affected!==[])$this->products->recalculateStockStatus($affected);$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_orders'))->set('stock_state='.$this->db->quote($state))->where('id='.$orderId);$this->db->setQuery($q)->execute();}
    private function consumeCoupon(int $orderId,int $userId,string $code,float $gross,float $tax,string $date):void
    {
        $coupon=$this->lockOne('#__fdshop_coupons','coupon_code='.$this->db->quote($code));
        if(!$coupon||(int)$coupon->published!==1)throw new \DomainException('Der Gutschein ist nicht mehr verfügbar.');
        $q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__fdshop_coupon_usage'))->where('coupon_id='.(int)$coupon->id);$this->db->setQuery($q);$total=(int)$this->db->loadResult();
        if((int)$coupon->usage_limit_total>0&&$total>=(int)$coupon->usage_limit_total)throw new \DomainException('Der Gutschein wurde inzwischen vollständig eingelöst.');
        $q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__fdshop_coupon_usage'))->where('coupon_id='.(int)$coupon->id)->where('user_id='.(int)$userId);$this->db->setQuery($q);$perUser=(int)$this->db->loadResult();
        if((int)$coupon->usage_limit_per_user>0&&$perUser>=(int)$coupon->usage_limit_per_user)throw new \DomainException('Sie haben diesen Gutschein inzwischen vollständig eingelöst.');
        $usage=(object)['coupon_id'=>(int)$coupon->id,'order_id'=>$orderId,'user_id'=>$userId,'coupon_code'=>$code,'discount_amount_net'=>round($gross/(1+$tax/100),4),'discount_amount_gross'=>$gross,'used_at'=>$date,'created_by'=>$userId];$this->db->insertObject('#__fdshop_coupon_usage',$usage);
    }
    private function deleteCart(int $userId,string $sessionId):void{foreach(['#__fdshop_cart_bundle_items i INNER JOIN #__fdshop_cart_bundles b ON b.id=i.cart_bundle_id'=>'i','#__fdshop_cart_bundles'=>'','#__fdshop_cart'=>''] as $table=>$alias){if($alias==='i'){$this->db->setQuery('DELETE i FROM '.$table.' WHERE b.user_id='.(int)$userId)->execute();}else{$q=$this->db->getQuery(true)->delete($this->db->quoteName($table))->where('user_id='.(int)$userId);$this->db->setQuery($q)->execute();}}}
    private function lockOne(string $table,string $where):?object{$this->db->setQuery('SELECT * FROM '.$this->db->quoteName($table).' WHERE '.$where.' FOR UPDATE');return $this->db->loadObject()?:null;}
    private function orderBySubmission(int $userId,string $id):?object{$q=$this->db->getQuery(true)->select(['id','order_number','grand_total','currency'])->from($this->db->quoteName('#__fdshop_orders'))->where('user_id='.(int)$userId)->where('submission_id='.$this->db->quote(strtolower($id)));$this->db->setQuery($q);return $this->db->loadObject()?:null;}
    private function orderNumber():string{$chars='ABCDEFGHJKLMNPQRSTUVWXYZ23456789';for($try=0;$try<20;$try++){$number=Factory::getDate()->format('ym',true);for($i=0;$i<4;$i++)$number.=$chars[random_int(0,strlen($chars)-1)];$q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__fdshop_orders'))->where('order_number='.$this->db->quote($number));$this->db->setQuery($q);if(!(int)$this->db->loadResult())return $number;}throw new \RuntimeException('Es konnte keine eindeutige Bestellnummer erzeugt werden.');}

    private function documentSnapshot(int $productId,int $specialCategoryId):array
    {
        $q=$this->db->getQuery(true)->select('COALESCE(path_invoice,path_small,path_standard,path_mobile,\'\')')->from($this->db->quoteName('#__fdshop_media'))->where('product_id='.(int)$productId)->where('media_type='.$this->db->quote('image'))->order('is_primary DESC, ordering ASC, id ASC');$this->db->setQuery($q,0,1);$image=(string)$this->db->loadResult();
        $group=1;if($specialCategoryId>0){$q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__fdshop_product_category_map'))->where('product_id='.(int)$productId)->where('category_id='.(int)$specialCategoryId);$this->db->setQuery($q);$group=(int)$this->db->loadResult()>0?2:1;}
        return ['image'=>$image?:null,'group'=>$group];
    }

    private function customerSnapshot(int $userId): array
    {
        $user = $this->lockOne('#__users', 'id = ' . $userId);
        if (!$user || !filter_var((string) $user->email, FILTER_VALIDATE_EMAIL)) {
            throw new \DomainException('Das Benutzerkonto besitzt keine gültige E-Mail-Adresse.');
        }
        $query = $this->db->getQuery(true)
            ->select([$this->db->quoteName('profile_key'), $this->db->quoteName('profile_value')])
            ->from($this->db->quoteName('#__user_profiles'))
            ->where($this->db->quoteName('user_id') . ' = ' . $userId)
            ->where($this->db->quoteName('profile_key') . ' LIKE ' . $this->db->quote('fdshop_customer.%'));
        $this->db->setQuery((string) $query . ' FOR UPDATE');
        $profile = [];
        foreach ($this->db->loadRowList() as [$key, $value]) {
            $profile[substr((string) $key, 16)] = trim((string) (json_decode($value, true) ?? $value));
        }
        $config = $this->customerFieldConfiguration();
        foreach ($config['required'] as $field) {
            if (($profile[$field] ?? '') === '') {
                throw new \DomainException('Bitte ergänzen Sie vor der Bestellung alle aktuell erforderlichen Kundendaten unter „Mein Profil bearbeiten“.');
            }
        }
        $firstName = mb_substr($profile['first_name'], 0, 100);
        $lastName = mb_substr($profile['last_name'], 0, 100);
        return [
            'name' => trim($firstName . ' ' . $lastName),
            'email' => (string) $user->email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'company' => mb_substr($config['active']['company'] ? ($profile['company'] ?? '') : '', 0, 255),
            'street' => mb_substr($config['active']['street'] ? ($profile['street'] ?? '') : '', 0, 255),
            'postal_code' => mb_substr($config['active']['postal_code'] ? ($profile['postal_code'] ?? '') : '', 0, 32),
            'city' => mb_substr($config['active']['city'] ? ($profile['city'] ?? '') : '', 0, 120),
            'country' => mb_substr($config['active']['country'] ? ($profile['country'] ?? '') : '', 0, 120),
            'phone' => mb_substr($config['active']['phone'] ? ($profile['phone'] ?? '') : '', 0, 64),
        ];
    }

    private function customerFieldConfiguration(): array
    {
        $defaults = ['company' => 1, 'street' => 2, 'postal_code' => 2, 'city' => 2, 'country' => 2, 'phone' => 1];
        $plugin = PluginHelper::getPlugin('user', 'fdshopprofile');
        $params = new Registry($plugin->params ?? '');
        $required = ['first_name', 'last_name'];
        $active = [];
        foreach ($defaults as $field => $default) {
            $status = (int) $params->get('field_' . $field, $default);
            $active[$field] = $status > 0;
            if ($status === 2) $required[] = $field;
        }
        return ['required' => $required, 'active' => $active];
    }
    private function result(object $o,bool $existing):array{return ['order_id'=>(int)$o->id,'order_number'=>(string)$o->order_number,'grand_total'=>(float)$o->grand_total,'currency'=>(string)$o->currency,'already_processed'=>$existing,'confirmation_url'=>'index.php?option=com_fdshop&view=checkoutconfirmation&order_number='.rawurlencode((string)$o->order_number)];}
}
