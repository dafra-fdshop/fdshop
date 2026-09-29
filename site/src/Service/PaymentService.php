<?php
namespace FDShop\Component\FDShop\Site\Service;
defined('_JEXEC') or die;

use FDShop\Component\FDShop\Administrator\Service\BuyerEligibilityServiceInterface;
use FDShop\Component\FDShop\Administrator\Service\ProductServiceInterface;
use Joomla\Database\DatabaseInterface;

final class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private readonly DatabaseInterface $db, private readonly CartServiceInterface $cart,
        private readonly CheckoutServiceInterface $checkout, private readonly PayPalClientInterface $paypal,
        private readonly BuyerEligibilityServiceInterface $eligibility, private readonly ProductServiceInterface $products,
        private readonly PaymentClockInterface $clock
    ) {}

    public function publicConfig(): array
    {
        return ['configured'=>$this->paypal->configured(),'mode'=>$this->paypal->mode(),'client_id'=>$this->paypal->clientId(),'sdk_url'=>$this->paypal->mode()==='live'?'https://www.paypal.com/web-sdk/v6/core':'https://www.sandbox.paypal.com/web-sdk/v6/core'];
    }

    public function start(int $userId,string $cartSessionId,int $shipmentId,int $paymentId,string $couponCode,string $note,bool $termsAccepted,string $submissionId):array
    {
        $this->cleanupExpired();
        if($userId<1)throw new \DomainException('Bitte melden Sie sich an, um mit PayPal zu bezahlen.');
        $this->checkout->assertCustomerReady($userId);
        if(!preg_match('/^[0-9a-f-]{36}$/i',$submissionId))throw new \DomainException('Die Zahlungsanfrage ist ungültig.');
        if(!$this->paypal->configured())throw new \DomainException('PayPal ist noch nicht vollständig konfiguriert.');
        $existing=$this->bySubmission($submissionId);
        if($existing){$this->assertOwner($existing,$userId);return $this->result($existing);}
        $cart=$this->cart->getCart($userId,$cartSessionId,$shipmentId,$paymentId,$couponCode);
        if($cart['items']===[]&&($cart['bundles']??[])===[])throw new \DomainException('Der Warenkorb ist leer.');
        if(!$cart['shipment']||!$cart['payment']||(int)$cart['payment']->paypal_enabled!==1)throw new \DomainException('Die gewählte Zahlungsart unterstützt PayPal nicht.');
        $q=$this->db->getQuery(true)->select(['katalog_active','require_terms_checkbox'])->from($this->db->quoteName('#__fdshop_config'))->where('id=1');$this->db->setQuery($q);$config=$this->db->loadObject();
        if(!$config||(int)$config->katalog_active===1)throw new \DomainException('Bestellungen sind im Katalogmodus nicht möglich.');
        if((int)$config->require_terms_checkbox===1&&!$termsAccepted)throw new \DomainException('Bitte bestätigen Sie die AGB und die Widerrufsbelehrung.');
        if(mb_strlen(trim(strip_tags($note)))>2000)throw new \DomainException('Die Bemerkung darf höchstens 2000 Zeichen lang sein.');
        if($couponCode!==''&&($cart['coupon_code']??'')==='')throw new \DomainException('Der Gutschein ist nicht mehr gültig.');
        $demand=[];$ids=[];
        foreach($cart['items'] as $item){$id=(int)$item->product_id;$ids[]=$id;$demand[$id]=($demand[$id]??0)+(float)$item->physical_quantity;}
        foreach(($cart['bundles']??[]) as $bundle)foreach($bundle->items as $item){$id=(int)$item->product_id;$ids[]=$id;$demand[$id]=($demand[$id]??0)+(float)$item->quantity;}
        $this->eligibility->assertProductsEligible($userId,$ids,'checkout');ksort($demand);
        $token=$this->uuid();$now=$this->clock->now();$minutes=$this->reservationMinutes();$expires=$now->modify('+'.$minutes.' minutes');
        $snapshot=['cart_session_id'=>$cartSessionId,'shipment_id'=>$shipmentId,'payment_id'=>$paymentId,'coupon_code'=>(string)($cart['coupon_code']??''),'note'=>mb_substr(trim(strip_tags($note)),0,2000),'terms_accepted'=>$termsAccepted,'submission_id'=>strtolower($submissionId),'demand'=>$demand];
        $amount=$this->minor($cart['total']);$currency=strtoupper((string)$cart['currency']);
        $this->db->transactionStart();
        try{
            foreach($demand as $productId=>$quantity){$stock=$this->lockProduct($productId);if(!$stock||((float)$stock->stock_quantity-(float)$stock->reserved_quantity)+0.0001<$quantity)throw new \DomainException('Mindestens ein Produkt ist nicht mehr in ausreichender Menge verfügbar.');}
            $row=(object)['session_token'=>$token,'submission_id'=>strtolower($submissionId),'user_id'=>$userId,'provider'=>'paypal','payment_method_id'=>$paymentId,'status'=>'reserved','snapshot_json'=>json_encode($snapshot,JSON_THROW_ON_ERROR),'amount_minor'=>$amount,'currency'=>$currency,'expires_at'=>$this->clock->toSql($expires),'created'=>$this->clock->toSql($now),'modified'=>$this->clock->toSql($now)];
            $this->db->insertObject('#__fdshop_payment_sessions',$row);$sessionId=(int)$this->db->insertid();
            foreach($demand as $productId=>$quantity){$reservation=(object)['payment_session_id'=>$sessionId,'product_id'=>$productId,'quantity'=>$quantity,'status'=>'reserved','created'=>$this->clock->toSql($now),'modified'=>$this->clock->toSql($now)];$this->db->insertObject('#__fdshop_payment_reservations',$reservation);$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_products_details'))->set('reserved_quantity=reserved_quantity+'.$this->db->quote($quantity))->where('product_id='.(int)$productId);$this->db->setQuery($q)->execute();}
            $this->products->recalculateStockStatus(array_keys($demand));$this->db->transactionCommit();
        }catch(\Throwable $e){$this->db->transactionRollback();throw $e;}
        try{$provider=$this->paypal->createOrder($token,$amount,$currency);$providerId=(string)($provider['id']??'');if($providerId==='')throw new \RuntimeException('PayPal hat keine Order-ID geliefert.');$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_payment_sessions'))->set('provider_order_id='.$this->db->quote($providerId))->set('status='.$this->db->quote('payment_in_progress'))->set('modified='.$this->db->quote($this->clock->toSql($this->clock->now())))->where('session_token='.$this->db->quote($token));$this->db->setQuery($q)->execute();}catch(\Throwable $e){$this->failAndRelease($token,'PayPal Create Order fehlgeschlagen.');throw $e;}
        return ['session_token'=>$token,'order_id'=>$providerId,'expires_at'=>$this->clock->toAtom($expires),'amount'=>number_format($amount/100,2,'.',''),'currency'=>$currency];
    }

    public function capture(int $userId,string $sessionToken,string $providerOrderId):array
    {
        $session=$this->session($sessionToken,true);$this->assertOwner($session,$userId);if((string)$session->provider_order_id!==$providerOrderId)throw new \DomainException('Die PayPal-Order gehört nicht zu dieser Zahlung.');
        if($session->order_id)return $this->orderResult((int)$session->order_id,true);
        $transaction=$this->transaction((int)$session->id);
        if(!$transaction||$transaction->provider_status!=='COMPLETED'){
            $now=$this->clock->now();$nowSql=$this->clock->toSql($now);
            if($this->clock->parseSql((string)$session->expires_at)<=$now){$this->release((int)$session->id,'expired');throw new \DomainException('Der Zahlungsvorgang ist abgelaufen.');}
            $q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_payment_sessions'))->set('status='.$this->db->quote('capturing'))->set('modified='.$this->db->quote($nowSql))->where('id='.(int)$session->id)->where("status IN ('reserved','payment_in_progress')")->where('expires_at>'.$this->db->quote($nowSql));$this->db->setQuery($q)->execute();if($this->db->getAffectedRows()!==1)throw new \DomainException('Der Zahlungsvorgang ist nicht mehr capturefähig.');
            try{$capture=$this->paypal->captureOrder($providerOrderId,$sessionToken.'-capture');$details=$this->captureDetails($capture);}
            catch(\Throwable $e){$this->setError((int)$session->id,'payment_in_progress','PayPal Capture konnte nicht bestätigt werden.');throw $e;}
            if($details['status']==='PENDING'){$this->setError((int)$session->id,'payment_in_progress','PayPal-Zahlung ist noch ausstehend.');throw new \DomainException('Die PayPal-Zahlung ist noch ausstehend.');}
            if($details['status']!=='COMPLETED'){$this->release((int)$session->id,'failed');throw new \DomainException('Die PayPal-Zahlung wurde nicht erfolgreich abgeschlossen.');}
            if($details['amount_minor']!==(int)$session->amount_minor||$details['currency']!==(string)$session->currency){$this->setError((int)$session->id,'captured','PayPal-Betrag oder Währung weicht ab.');throw new \RuntimeException('Die bestätigte PayPal-Zahlung stimmt nicht mit dem erwarteten Betrag überein.');}
            $this->persistCapture($session,$details);$session=$this->session($sessionToken,true);
        }
        return $this->finalize($session);
    }

    public function cleanupExpired():int
    {
        $now=$this->clock->toSql($this->clock->now());$q=$this->db->getQuery(true)->select('id')->from($this->db->quoteName('#__fdshop_payment_sessions'))->where('expires_at<='.$this->db->quote($now))->where("status IN ('created','reserved','payment_in_progress')");$this->db->setQuery($q);$count=0;foreach($this->db->loadColumn() as $id){if($this->release((int)$id,'expired'))$count++;}return $count;
    }

    public function webhook(array $headers,string $body):void
    {
        if(!$this->paypal->verifyWebhook($headers,$body))throw new \RuntimeException('Ungültige PayPal-Webhook-Signatur.',403);
        $event=json_decode($body,true,512,JSON_THROW_ON_ERROR);if(($event['event_type']??'')!=='PAYMENT.CAPTURE.COMPLETED')return;
        $providerOrderId=(string)($event['resource']['supplementary_data']['related_ids']['order_id']??'');if($providerOrderId==='')return;
        $q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_payment_sessions'))->where('provider='.$this->db->quote('paypal'))->where('provider_order_id='.$this->db->quote($providerOrderId));$this->db->setQuery($q);$session=$this->db->loadObject();if(!$session)return;
        if($session->order_id)return;
        $details=['status'=>'COMPLETED','capture_id'=>(string)($event['resource']['id']??''),'amount_minor'=>$this->minor($event['resource']['amount']['value']??'0'),'currency'=>strtoupper((string)($event['resource']['amount']['currency_code']??''))];
        if($details['amount_minor']!==(int)$session->amount_minor||$details['currency']!==(string)$session->currency)throw new \RuntimeException('Webhook-Betrag stimmt nicht überein.');
        $this->persistCapture($session,$details);$this->finalize($this->session((string)$session->session_token,true));
    }

    private function finalize(object $session):array
    {
        if($session->order_id)return $this->orderResult((int)$session->order_id,true);$snapshot=json_decode((string)$session->snapshot_json,true,512,JSON_THROW_ON_ERROR);
        try{$result=$this->checkout->createOrder((int)$session->user_id,(string)$snapshot['cart_session_id'],(int)$snapshot['shipment_id'],(int)$snapshot['payment_id'],(string)$snapshot['coupon_code'],(string)$snapshot['note'],(bool)$snapshot['terms_accepted'],(string)$snapshot['submission_id'],(string)$session->session_token,'paid');
            $now=$this->clock->toSql($this->clock->now());$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_payment_sessions'))->set('status='.$this->db->quote('completed'))->set('order_id='.(int)$result['order_id'])->set('modified='.$this->db->quote($now))->where('id='.(int)$session->id);$this->db->setQuery($q)->execute();$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_payment_transactions'))->set('finalized_at='.$this->db->quote($now))->set('modified='.$this->db->quote($now))->where('payment_session_id='.(int)$session->id);$this->db->setQuery($q)->execute();return $result;
        }catch(\Throwable $e){$this->setError((int)$session->id,'captured',mb_substr($e->getMessage(),0,1000));throw $e;}
    }

    private function persistCapture(object $session,array $d):void{$now=$this->clock->toSql($this->clock->now());$row=(object)['payment_session_id'=>(int)$session->id,'provider'=>'paypal','provider_order_id'=>(string)$session->provider_order_id,'capture_id'=>$d['capture_id'],'provider_status'=>'COMPLETED','amount_minor'=>$d['amount_minor'],'currency'=>$d['currency'],'captured_at'=>$now,'created'=>$now,'modified'=>$now];try{$this->db->insertObject('#__fdshop_payment_transactions',$row);}catch(\Throwable){$tx=$this->transaction((int)$session->id);if(!$tx||$tx->provider_status!=='COMPLETED')throw new \RuntimeException('Der PayPal-Capture konnte nicht idempotent gespeichert werden.');}$this->setStatus((int)$session->id,'captured');}
    private function captureDetails(array $r):array{$c=$r['purchase_units'][0]['payments']['captures'][0]??[];return ['status'=>strtoupper((string)($c['status']??$r['status']??'')),'capture_id'=>(string)($c['id']??''),'amount_minor'=>$this->minor($c['amount']['value']??'0'),'currency'=>strtoupper((string)($c['amount']['currency_code']??''))];}
    private function release(int $id,string $status):bool{$this->db->transactionStart();try{$s=$this->lockSession($id);if(!$s||!in_array($s->status,['created','reserved','payment_in_progress','failed'],true)||$s->reservation_released_at){$this->db->transactionCommit();return false;}$q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_payment_reservations'))->where('payment_session_id='.$id)->where('status='.$this->db->quote('reserved'))->order('product_id ASC');$this->db->setQuery((string)$q.' FOR UPDATE');$rows=$this->db->loadObjectList();foreach($rows as $r){$this->lockProduct((int)$r->product_id);$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_products_details'))->set('reserved_quantity=GREATEST(0,reserved_quantity-'.$this->db->quote($r->quantity).')')->where('product_id='.(int)$r->product_id);$this->db->setQuery($q)->execute();}$now=$this->clock->toSql($this->clock->now());$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_payment_reservations'))->set('status='.$this->db->quote('released'))->set('modified='.$this->db->quote($now))->where('payment_session_id='.$id)->where('status='.$this->db->quote('reserved'));$this->db->setQuery($q)->execute();$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_payment_sessions'))->set('status='.$this->db->quote($status))->set('reservation_released_at='.$this->db->quote($now))->set('modified='.$this->db->quote($now))->where('id='.$id);$this->db->setQuery($q)->execute();$this->products->recalculateStockStatus(array_map(fn($r)=>(int)$r->product_id,$rows));$this->db->transactionCommit();return true;}catch(\Throwable $e){$this->db->transactionRollback();throw $e;}}
    private function failAndRelease(string $token,string $message):void{$s=$this->session($token,false);if($s){$this->setError((int)$s->id,'failed',$message);$this->release((int)$s->id,'failed');}}
    private function setStatus(int $id,string $status):void{$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_payment_sessions'))->set('status='.$this->db->quote($status))->set('modified='.$this->db->quote($this->clock->toSql($this->clock->now())))->where('id='.$id);$this->db->setQuery($q)->execute();}
    private function setError(int $id,string $status,string $message):void{$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_payment_sessions'))->set('status='.$this->db->quote($status))->set('last_error='.$this->db->quote(mb_substr($message,0,1000)))->set('modified='.$this->db->quote($this->clock->toSql($this->clock->now())))->where('id='.$id);$this->db->setQuery($q)->execute();}
    private function session(string $token,bool $required):?object{$q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_payment_sessions'))->where('session_token='.$this->db->quote($token));$this->db->setQuery($q);$s=$this->db->loadObject()?:null;if($required&&!$s)throw new \DomainException('Die Zahlungssitzung wurde nicht gefunden.');return $s;}
    private function bySubmission(string $id):?object{$q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_payment_sessions'))->where('submission_id='.$this->db->quote(strtolower($id)));$this->db->setQuery($q);return $this->db->loadObject()?:null;}
    private function transaction(int $id):?object{$q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_payment_transactions'))->where('payment_session_id='.$id);$this->db->setQuery($q);return $this->db->loadObject()?:null;}
    private function lockSession(int $id):?object{$this->db->setQuery('SELECT * FROM '.$this->db->quoteName('#__fdshop_payment_sessions').' WHERE id='.$id.' FOR UPDATE');return $this->db->loadObject()?:null;}
    private function lockProduct(int $id):?object{$this->db->setQuery('SELECT product_id,stock_quantity,reserved_quantity FROM '.$this->db->quoteName('#__fdshop_products_details').' WHERE product_id='.$id.' FOR UPDATE');return $this->db->loadObject()?:null;}
    private function assertOwner(object $s,int $user):void{if((int)$s->user_id!==$user)throw new \DomainException('Diese Zahlungssitzung gehört nicht zu Ihrem Benutzerkonto.');}
    private function result(object $s):array{return ['session_token'=>(string)$s->session_token,'order_id'=>(string)$s->provider_order_id,'expires_at'=>$this->clock->toAtom($this->clock->parseSql((string)$s->expires_at)),'amount'=>number_format((int)$s->amount_minor/100,2,'.',''),'currency'=>(string)$s->currency,'status'=>(string)$s->status];}
    private function orderResult(int $id,bool $existing):array{$q=$this->db->getQuery(true)->select(['id','order_number','grand_total','currency'])->from($this->db->quoteName('#__fdshop_orders'))->where('id='.$id);$this->db->setQuery($q);$o=$this->db->loadObject();if(!$o)throw new \RuntimeException('Die finalisierte Bestellung wurde nicht gefunden.');return ['order_id'=>(int)$o->id,'order_number'=>(string)$o->order_number,'grand_total'=>(float)$o->grand_total,'currency'=>(string)$o->currency,'already_processed'=>$existing,'confirmation_url'=>'index.php?option=com_fdshop&view=checkoutconfirmation&order='.rawurlencode((string)$o->order_number)];}
    private function minor(mixed $value):int
    {
        $decimal = trim(str_replace(',', '.', (string) $value));
        if (!preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', $decimal, $matches)) {
            throw new \RuntimeException('Ungültiger Geldbetrag.');
        }

        $fraction = str_pad($matches[3] ?? '', 3, '0');
        $minor = ((int) $matches[2] * 100) + (int) substr($fraction, 0, 2);
        if ((int) $fraction[2] >= 5) {
            ++$minor;
        }

        return ($matches[1] ?? '') === '-' ? -$minor : $minor;
    }
    private function reservationMinutes():int{$q=$this->db->getQuery(true)->select('paypal_reservation_minutes')->from($this->db->quoteName('#__fdshop_config'))->where('id=1');$this->db->setQuery($q);return max(5,min(30,(int)$this->db->loadResult()?:10));}
    private function uuid():string{$h=bin2hex(random_bytes(16));return substr($h,0,8).'-'.substr($h,8,4).'-4'.substr($h,13,3).'-'.dechex((hexdec($h[16])&3)|8).substr($h,17,3).'-'.substr($h,20);}
}
