<?php
namespace FDShop\Component\FDShop\Site\Controller;
defined('_JEXEC') or die;
use FDShop\Component\FDShop\Site\Service\AccountServiceInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
final class AccountController extends BaseController
{
    protected $default_view='account';
    public function saveProfile():void{$this->post(fn($s,$u)=>$s->saveProfile($u,(array)$this->input->post->get('profile',[],'array')),'Kontaktdaten wurden gespeichert.','profile');}
    public function saveCredentials():void{$this->post(function($s,$u){$password=$this->input->post->getString('password')?:null;$confirmation=$this->input->post->getString('password2')?:null;if($password!==null&&$password!==$confirmation)throw new \DomainException('Die Passwortbestätigung stimmt nicht überein.');$s->saveCredentials($u,$this->input->post->getString('username')?:null,$password);},'Kontodaten wurden gespeichert.','security');}
    public function removeWatch():void{$this->post(fn($s,$u)=>$s->removeWatch($u,$this->input->post->getInt('watch_id')),'Benachrichtigung wurde entfernt.','watchlist');}
    public function requestEmail():void{$this->post(fn($s,$u)=>$s->requestEmailChange($u,$this->input->post->getString('email')),'Bitte bestätigen Sie die neue Adresse über die zugesandte E-Mail.','security');}
    public function requestShipment():void{$this->post(fn($s,$u)=>$s->requestShipment($u,$this->input->post->getInt('order_id'),$this->input->post->getInt('shipment_id')),'Die Änderungsanfrage wurde versendet.','orders');}
    public function withdraw():void{$this->post(fn($s,$u)=>$s->declareWithdrawal($u,$this->input->post->getInt('order_id')),'Der Eingang Ihres Widerrufs wurde bestätigt.','orders');}
    public function uploadF3():void{$this->post(fn($s,$u)=>$s->submitF3Documents($u,['permit'=>$_FILES['permit']??null,'identity'=>$_FILES['identity']??null]),'Die Unterlagen wurden sicher übermittelt und lokal gelöscht.','f3');}
    public function verifyEmail():void{$app=Factory::getApplication();try{$this->service()->verifyEmail($this->input->getString('token'));$app->enqueueMessage('Ihre neue E-Mail-Adresse wurde bestätigt.');}catch(\Throwable $e){$app->enqueueMessage($e->getMessage(),'error');}$app->redirect(Route::_('index.php?option=com_fdshop&view=account&section=security',false));}
    private function post(callable $callback,string $success,string $section):void{$app=Factory::getApplication();try{if(strtoupper($this->input->getMethod())!=='POST'||!Session::checkToken())throw new \RuntimeException('Ungültiger Sicherheitstoken.');$userId=(int)$app->getIdentity()->id;if($userId<1)throw new \DomainException('Bitte melden Sie sich an.');$callback($this->service(),$userId);$app->enqueueMessage($success);}catch(\Throwable $e){$app->enqueueMessage($e->getMessage(),'error');}$app->redirect(Route::_('index.php?option=com_fdshop&view=account&section='.$section,false));}
    private function service():AccountServiceInterface{return Factory::getApplication()->bootComponent('com_fdshop')->getContainer()->get(AccountServiceInterface::class);}
}
