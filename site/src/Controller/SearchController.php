<?php
namespace FDShop\Component\FDShop\Site\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
final class SearchController extends BaseController{public function suggest():void{$app=Factory::getApplication();try{$model=$this->getModel('Search');echo new JsonResponse(['items'=>$model->getSuggestions(8),'query'=>trim($this->input->getString('q',''))]);}catch(\Throwable $e){echo new JsonResponse(null,$e->getMessage(),true);}$app->close();}}
