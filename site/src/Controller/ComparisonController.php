<?php
namespace FDShop\Component\FDShop\Site\Controller;
defined('_JEXEC') or die;
use FDShop\Component\FDShop\Site\Helper\ComparisonHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
final class ComparisonController extends BaseController
{
 public function state():void{$this->json(fn($s,$u)=>$s->state()+['saved'=>$u?$s->saved($u):[],'categories'=>$s->categories((array)$this->input->get('product_ids',[],'array'))],false);}
 public function add():void{$this->json(fn($s)=>$s->add($this->input->post->getInt('product_id'),$this->input->post->getInt('category_id'),(bool)$this->input->post->getBool('replace')));}
 public function remove():void{$this->json(fn($s)=>$s->remove($this->input->post->getInt('product_id')));}
 public function clear():void{$this->json(fn($s)=>$s->clear());}
 public function save():void{$this->json(fn($s,$u)=>['list'=>$s->save($u,$this->input->post->getString('name'))]);}
 public function activate():void{$this->json(fn($s,$u)=>$s->activate($u,$this->input->post->getInt('list_id')));}
 public function rename():void{$this->json(function($s,$u){$s->rename($u,$this->input->post->getInt('list_id'),$this->input->post->getString('name'));return['saved'=>true];});}
 public function delete():void{$this->json(function($s,$u){$s->delete($u,$this->input->post->getInt('list_id'));return['deleted'=>true];});}
 private function json(callable$callback,bool$csrf=true):void{$app=Factory::getApplication();try{if($csrf&&(strtoupper($this->input->getMethod())!=='POST'||!Session::checkToken('request')))throw new \RuntimeException('Ungültiger Sicherheitstoken.');$user=(int)$app->getIdentity()->id;echo new JsonResponse($callback(ComparisonHelper::service(),$user));}catch(\Throwable$e){echo new JsonResponse(null,$e->getMessage(),true);}$app->close();}
}
