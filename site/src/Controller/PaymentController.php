<?php
namespace FDShop\Component\FDShop\Site\Controller;
defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Service\PaymentServiceInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

final class PaymentController extends BaseController
{
    public function start():void{$this->json(function(PaymentServiceInterface $service):array{$app=Factory::getApplication();$user=(int)$app->getIdentity()->id;$session=$app->getSession();$input=$app->getInput();return $service->start($user,$session->getId(),(int)$session->get($this->key($user,'shipment_id'),0),(int)$session->get($this->key($user,'payment_id'),0),(string)$session->get($this->key($user,'coupon_code'),''),$input->post->getString('order_note'),$input->post->getInt('terms_accepted')===1,$input->post->getString('submission_id'));});}
    public function capture():void{$this->json(function(PaymentServiceInterface $service):array{$input=Factory::getApplication()->getInput();return $service->capture((int)Factory::getApplication()->getIdentity()->id,$input->post->getString('session_token'),$input->post->getString('provider_order_id'));});}
    public function config():void{$this->json(fn(PaymentServiceInterface $service):array=>$service->publicConfig(),false);}
    public function webhook():void{$app=Factory::getApplication();try{$headers=[];foreach($_SERVER as $key=>$value)if(str_starts_with($key,'HTTP_PAYPAL_'))$headers[strtolower(str_replace('_','-',substr($key,5)))]=(string)$value;$this->service()->webhook($headers,(string)file_get_contents('php://input'));echo new JsonResponse(['accepted'=>true]);}catch(\Throwable $e){http_response_code($e->getCode()===403?403:400);echo new JsonResponse(null,'Webhook abgelehnt.',true);}$app->close();}
    private function json(callable $callback,bool $csrf=true):void{$app=Factory::getApplication();try{if($csrf&&!Session::checkToken('request'))throw new \RuntimeException('Ungültiger Sicherheitstoken.');echo new JsonResponse($callback($this->service()));}catch(\Throwable $e){echo new JsonResponse(null,$e->getMessage(),true);}$app->close();}
    private function service():PaymentServiceInterface{return Factory::getApplication()->bootComponent('com_fdshop')->getContainer()->get(PaymentServiceInterface::class);}
    private function key(int $user,string $field):string{return 'com_fdshop.cart.'.$user.'.'.$field;}
}
