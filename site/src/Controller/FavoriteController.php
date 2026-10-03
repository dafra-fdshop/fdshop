<?php
namespace FDShop\Component\FDShop\Site\Controller;
defined('_JEXEC') or die;
use FDShop\Component\FDShop\Site\Helper\FavoriteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
final class FavoriteController extends BaseController
{
 public function states():void{$app=Factory::getApplication();try{$service=FavoriteHelper::service();$ids=(array)$this->input->get('product_ids',[],'array');$user=(int)$app->getIdentity()->id;echo new JsonResponse(['enabled'=>$service->enabled(),'guest'=>$user<1,'states'=>$user?$service->states($user,$ids):[]]);}catch(\Throwable$e){echo new JsonResponse(null,$e->getMessage(),true);}$app->close();}
 public function counter():void{$app=Factory::getApplication();try{$service=FavoriteHelper::service();$user=(int)$app->getIdentity()->id;echo new JsonResponse(['enabled'=>$service->enabled(),'guest'=>$user<1,'count'=>$service->defaultCount($user)]);}catch(\Throwable$e){echo new JsonResponse(null,$e->getMessage(),true);}$app->close();}
 public function toggle():void{$this->json(fn($s,$u)=>$s->toggleDefault($u,$this->input->post->getInt('product_id')));}
 public function lists():void{$this->json(fn($s,$u)=>['lists'=>$s->lists($u,$this->input->getInt('product_id'))],false);}
 public function saveMemberships():void{$this->json(fn($s,$u)=>$s->saveMemberships($u,$this->input->post->getInt('product_id'),(array)$this->input->post->get('list_ids',[],'array')));}
 public function createList():void{$this->json(fn($s,$u)=>['list'=>$s->createList($u,$this->input->post->getString('name'))]);}
 public function renameList():void{$this->json(function($s,$u){$s->renameList($u,$this->input->post->getInt('list_id'),$this->input->post->getString('name'));return['saved'=>true];});}
 public function deleteList():void{$this->json(function($s,$u){$s->deleteList($u,$this->input->post->getInt('list_id'));return['deleted'=>true];});}
 private function json(callable$callback,bool$csrf=true):void{$app=Factory::getApplication();try{if($csrf&&(strtoupper($this->input->getMethod())!=='POST'||!Session::checkToken('request')))throw new \RuntimeException('Ungültiger Sicherheitstoken.');$user=(int)$app->getIdentity()->id;if($user<1)throw new \DomainException('Bitte melde dich an.');echo new JsonResponse($callback(FavoriteHelper::service(),$user));}catch(\Throwable$e){echo new JsonResponse(null,$e->getMessage(),true);}$app->close();}
}
