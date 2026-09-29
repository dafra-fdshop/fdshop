<?php
declare(strict_types=1);
define('_JEXEC',1);define('JPATH_BASE','/var/www/html');require JPATH_BASE.'/includes/defines.php';require JPATH_BASE.'/includes/framework.php';
require_once JPATH_ADMINISTRATOR.'/components/com_fdshop/src/Extension/FdshopComponent.php';
require_once JPATH_ROOT.'/components/com_fdshop/src/Service/PayPalClientInterface.php';
require_once JPATH_ROOT.'/components/com_fdshop/src/Service/PaymentServiceInterface.php';
require_once JPATH_ROOT.'/components/com_fdshop/src/Service/PaymentService.php';
foreach (glob(JPATH_ADMINISTRATOR.'/components/com_fdshop/src/Service/*Interface.php') as $file) require_once $file;
foreach (glob(JPATH_ROOT.'/components/com_fdshop/src/Service/*Interface.php') as $file) require_once $file;
foreach (glob(JPATH_ADMINISTRATOR.'/components/com_fdshop/src/Service/*.php') as $file) require_once $file;
foreach (glob(JPATH_ROOT.'/components/com_fdshop/src/Service/*.php') as $file) require_once $file;

use FDShop\Component\FDShop\Administrator\Service\BuyerEligibilityServiceInterface;
use FDShop\Component\FDShop\Administrator\Service\ProductServiceInterface;
use FDShop\Component\FDShop\Site\Service\CartServiceInterface;
use FDShop\Component\FDShop\Site\Service\CheckoutServiceInterface;
use FDShop\Component\FDShop\Site\Service\PaymentService;
use FDShop\Component\FDShop\Site\Service\PaymentClockInterface;
use FDShop\Component\FDShop\Site\Service\PayPalClientInterface;
use Joomla\CMS\Application\SiteApplication;use Joomla\CMS\Factory;use Joomla\Database\DatabaseInterface;use Joomla\Session\SessionInterface;

final class FakePayPal implements PayPalClientInterface{
 public int $captureCalls=0;private int $amount=0;private string $currency='EUR';private int $sequence=0;
 public function configured():bool{return true;}public function mode():string{return 'sandbox';}public function clientId():string{return 'public-test-id';}
 public function createOrder(string $requestId,int $amountMinor,string $currency):array{$this->amount=$amountMinor;$this->currency=$currency;return ['id'=>'FAKE-ORDER-'.(++$this->sequence),'status'=>'CREATED'];}
 public function captureOrder(string $providerOrderId,string $requestId):array{$this->captureCalls++;return ['status'=>'COMPLETED','purchase_units'=>[['payments'=>['captures'=>[['id'=>'FAKE-CAPTURE-'.$this->sequence,'status'=>'COMPLETED','amount'=>['value'=>number_format($this->amount/100,2,'.',''),'currency_code'=>$this->currency]]]]]]];}
 public function verifyWebhook(array $headers,string $body):bool{return ($headers['x-test-valid']??'')==='1';}
}

final class FakePaymentClock implements PaymentClockInterface{
 private readonly DateTimeZone $utc;
 public function __construct(private DateTimeImmutable $current){$this->utc=new DateTimeZone('UTC');$this->current=$this->current->setTimezone($this->utc);}
 public function set(string $value):void{$this->current=new DateTimeImmutable($value,$this->utc);}
 public function now():DateTimeImmutable{return $this->current;}
 public function parseSql(string $value):DateTimeImmutable{$date=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$value,$this->utc);if(!$date)throw new UnexpectedValueException('Invalid test SQL timestamp.');return $date;}
 public function toSql(DateTimeInterface $value):string{return DateTimeImmutable::createFromInterface($value)->setTimezone($this->utc)->format('Y-m-d H:i:s');}
 public function toAtom(DateTimeInterface $value):string{return DateTimeImmutable::createFromInterface($value)->setTimezone($this->utc)->format(DATE_ATOM);}
}

$_SERVER['HTTP_HOST']='localhost';$_SERVER['REQUEST_URI']='/';$_SERVER['SCRIPT_NAME']='/index.php';
$container=Factory::getContainer();$container->alias(SessionInterface::class,'session.web.site');$app=$container->get(SiteApplication::class);Factory::$application=$app;$db=$container->get(DatabaseInterface::class);$services=$app->bootComponent('com_fdshop')->getContainer();
$fake=new FakePayPal();$clock=new FakePaymentClock(new DateTimeImmutable('2026-07-15 10:00:00',new DateTimeZone('UTC')));$payment=new PaymentService($db,$services->get(CartServiceInterface::class),$services->get(CheckoutServiceInterface::class),$fake,$services->get(BuyerEligibilityServiceInterface::class),$services->get(ProductServiceInterface::class),$clock);$cart=$services->get(CartServiceInterface::class);
$assert=static function(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);};$scalar=static function(string $sql)use($db){$db->setQuery($sql);return $db->loadResult();};$user=(int)$scalar('SELECT id FROM #__users ORDER BY id LIMIT 1');
$db->setQuery("SELECT user_id,profile_key,profile_value,ordering FROM #__user_profiles WHERE user_id={$user} AND profile_key LIKE 'fdshop_customer.%'");$oldProfiles=$db->loadObjectList();
$profiles=['first_name'=>'PayPal','last_name'=>'Tester','street'=>'Testweg 1','postal_code'=>'12345','city'=>'Teststadt','country'=>'Deutschland'];
try{
 $db->setQuery("DELETE FROM #__user_profiles WHERE user_id={$user} AND profile_key LIKE 'fdshop_customer.%'")->execute();foreach($profiles as $key=>$value){$row=(object)['user_id'=>$user,'profile_key'=>'fdshop_customer.'.$key,'profile_value'=>json_encode($value),'ordering'=>1];$db->insertObject('#__user_profiles',$row);}
 $db->setQuery('UPDATE #__fdshop_payment_methods SET paypal_enabled=1,published=1 WHERE id=900610')->execute();
 $session='paypal-service-regression';$submission='11111111-1111-4111-8111-111111111111';$cart->addItem($user,$session,900100,1,'piece');
 try{$services->get(CheckoutServiceInterface::class)->createOrder($user,$session,900600,900610,'','',true,'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');throw new RuntimeException('PayPal method bypassed the payment flow.');}catch(DomainException $e){if($e->getMessage()==='PayPal method bypassed the payment flow.')throw $e;}
 $before=(float)$scalar('SELECT reserved_quantity FROM #__fdshop_products_details WHERE product_id=900100');
 $started=$payment->start($user,$session,900600,900610,'','Regression',true,$submission);
 $created=(string)$scalar("SELECT created FROM #__fdshop_payment_sessions WHERE session_token=".$db->quote($started['session_token']));$expires=(string)$scalar("SELECT expires_at FROM #__fdshop_payment_sessions WHERE session_token=".$db->quote($started['session_token']));
 $assert($clock->parseSql($expires)->getTimestamp()-$clock->parseSql($created)->getTimestamp()===600,'Payment reservation lifetime was not exactly 600 seconds.');
 $assert($started['expires_at']==='2026-07-15T10:10:00+00:00','Payment expiry was not serialized with an explicit UTC offset.');
 try{$payment->capture($user+99999,$started['session_token'],$started['order_id']);throw new RuntimeException('Foreign user accessed payment session.');}catch(DomainException $e){if($e->getMessage()==='Foreign user accessed payment session.')throw $e;}
 $assert((int)$scalar("SELECT COUNT(*) FROM #__fdshop_orders WHERE submission_id='{$submission}'")===0,'Order existed before capture.');
 $assert((float)$scalar('SELECT reserved_quantity FROM #__fdshop_products_details WHERE product_id=900100')===$before+1.0,'Temporary reservation missing.');
 try{$payment->capture($user,$started['session_token'],'FOREIGN-ORDER');throw new RuntimeException('Foreign provider order accepted.');}catch(DomainException){}
 $db->setQuery("UPDATE #__fdshop_order_statuses SET is_active=0 WHERE status_code='paid'")->execute();
 try{$payment->capture($user,$started['session_token'],$started['order_id']);throw new RuntimeException('Finalizer failure was not surfaced.');}catch(RuntimeException){}
 $assert($fake->captureCalls===1,'Capture was not called exactly once.');
 $assert((string)$scalar("SELECT provider_status FROM #__fdshop_payment_transactions WHERE provider_order_id=".$db->quote($started['order_id']))==='COMPLETED','Completed capture was not persisted before finalizer retry.');
 $db->setQuery("UPDATE #__fdshop_order_statuses SET is_active=1 WHERE status_code='paid'")->execute();
 $result=$payment->capture($user,$started['session_token'],$started['order_id']);$repeat=$payment->capture($user,$started['session_token'],$started['order_id']);
 $assert($fake->captureCalls===1,'Retry captured a second time.');$assert($result['order_id']===$repeat['order_id'],'Idempotent retry created a second order.');
 $event=json_encode(['event_type'=>'PAYMENT.CAPTURE.COMPLETED','resource'=>['id'=>'FAKE-CAPTURE-1','amount'=>['value'=>$started['amount'],'currency_code'=>$started['currency']],'supplementary_data'=>['related_ids'=>['order_id'=>$started['order_id']]]]],JSON_THROW_ON_ERROR);
 try{$payment->webhook(['x-test-valid'=>'0'],$event);throw new RuntimeException('Invalid webhook signature accepted.');}catch(RuntimeException $e){if($e->getMessage()==='Invalid webhook signature accepted.')throw $e;}
 $payment->webhook(['x-test-valid'=>'1'],$event);$payment->webhook(['x-test-valid'=>'1'],$event);
 $assert((int)$scalar("SELECT COUNT(*) FROM #__fdshop_orders WHERE submission_id='{$submission}' AND order_status='paid'")===1,'Exactly one paid order was not created.');
 $assert((int)$scalar("SELECT COUNT(*) FROM #__fdshop_cart WHERE user_id={$user}")===0,'Cart was not removed after finalization.');

 $clock->set('2026-07-15 11:00:00');$session2='paypal-expiry-regression';$submission2='22222222-2222-4222-8222-222222222222';$cart->addItem($user,$session2,900100,1,'piece');$started2=$payment->start($user,$session2,900600,900610,'','',true,$submission2);$clock->set('2026-07-15 11:09:59');$assert($payment->cleanupExpired()===0,'Active reservation was released before expiry.');$clock->set('2026-07-15 11:10:00');$assert($payment->cleanupExpired()===1,'Reservation was not released exactly at expiry.');$assert($payment->cleanupExpired()===0,'Expired reservation was released twice.');$assert((int)$scalar("SELECT COUNT(*) FROM #__fdshop_cart WHERE user_id={$user}")===1,'Expiry removed the cart.');

 $db->setQuery('DELETE FROM #__fdshop_cart WHERE user_id='.$user)->execute();$clock->set('2026-07-15 12:00:00');$sessionExpiredCapture='paypal-expired-capture';$submissionExpiredCapture='44444444-4444-4444-8444-444444444444';$cart->addItem($user,$sessionExpiredCapture,900100,1,'piece');$expiredCapture=$payment->start($user,$sessionExpiredCapture,900600,900610,'','',true,$submissionExpiredCapture);$captureCalls=$fake->captureCalls;$clock->set('2026-07-15 12:10:00');try{$payment->capture($user,$expiredCapture['session_token'],$expiredCapture['order_id']);throw new RuntimeException('Capture at expiry was accepted.');}catch(DomainException $e){$assert($e->getMessage()==='Der Zahlungsvorgang ist abgelaufen.','Unexpected expired capture error.');}$assert($fake->captureCalls===$captureCalls,'PayPal capture was called for an expired session.');

 $db->setQuery('DELETE FROM #__fdshop_cart WHERE user_id='.$user)->execute();$clock->set('2026-07-15 13:00:00');$cart->addItem($user,'paypal-multi-old',900100,1,'piece');$old=$payment->start($user,'paypal-multi-old',900600,900610,'','',true,'55555555-5555-4555-8555-555555555555');$clock->set('2026-07-15 13:05:00');$cart->addItem($user,'paypal-multi-active',900100,1,'piece');$active=$payment->start($user,'paypal-multi-active',900600,900610,'','',true,'66666666-6666-4666-8666-666666666666');$clock->set('2026-07-15 13:10:00');$assert($payment->cleanupExpired()===1,'Cleanup did not release exactly the individually expired session.');$assert((string)$scalar("SELECT status FROM #__fdshop_payment_sessions WHERE session_token=".$db->quote($old['session_token']))==='expired','Expired session kept its reservation.');$assert((string)$scalar("SELECT status FROM #__fdshop_payment_sessions WHERE session_token=".$db->quote($active['session_token']))==='payment_in_progress','Cleanup released a different active session.');

 $db->setQuery('DELETE FROM #__fdshop_cart WHERE user_id='.$user)->execute();$clock->set('2026-07-15 14:00:00');$session3='paypal-race-regression';$submission3='33333333-3333-4333-8333-333333333333';$cart->addItem($user,$session3,900100,1,'piece');$started3=$payment->start($user,$session3,900600,900610,'','',true,$submission3);$db->setQuery("UPDATE #__fdshop_payment_sessions SET status='capturing',expires_at='2000-01-01 00:00:00' WHERE session_token=".$db->quote($started3['session_token']))->execute();$reserved=(float)$scalar('SELECT reserved_quantity FROM #__fdshop_products_details WHERE product_id=900100');$assert($payment->cleanupExpired()===0,'Cleaner released a capturing payment.');$assert((float)$scalar('SELECT reserved_quantity FROM #__fdshop_products_details WHERE product_id=900100')===$reserved,'Capturing reservation changed during cleanup.');
 echo "PayPal payment service regression: PASS\n";
}finally{
 $db->setQuery("UPDATE #__fdshop_order_statuses SET is_active=1 WHERE status_code='paid'")->execute();
 $db->setQuery("DELETE FROM #__user_profiles WHERE user_id={$user} AND profile_key LIKE 'fdshop_customer.%'")->execute();foreach($oldProfiles as $profile)$db->insertObject('#__user_profiles',$profile);
}
