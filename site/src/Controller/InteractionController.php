<?php
namespace FDShop\Component\FDShop\Site\Controller;
defined('_JEXEC') or die;

use FDShop\Component\FDShop\Administrator\Service\WatchlistServiceInterface;
use FDShop\Component\FDShop\Site\Helper\RouteHelper;
use FDShop\Component\FDShop\Site\Service\ProductQuestionServiceInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

final class InteractionController extends BaseController
{
    public function watch():void
    {
        $app=Factory::getApplication();try{if(strtoupper($this->input->getMethod())!=='POST'||!Session::checkToken('request'))throw new \RuntimeException('Ungültiger Sicherheitstoken.');$userId=(int)$app->getIdentity()->id;if($userId<1)throw new \DomainException('Bitte melden Sie sich an.');$this->watchlist()->activate($userId,$this->input->post->getInt('product_id'));echo new JsonResponse(['active'=>true],'Benachrichtigung wurde aktiviert.');}catch(\Throwable $e){echo new JsonResponse(null,$e->getMessage(),true);}$app->close();
    }

    public function question():void
    {
        $app=Factory::getApplication();$productId=$this->input->post->getInt('product_id');
        try{if(strtoupper($this->input->getMethod())!=='POST'||!Session::checkToken())throw new \RuntimeException('Ungültiger Sicherheitstoken.');$form=Form::getInstance('com_fdshop.product_question',JPATH_COMPONENT_SITE.'/forms/product_question.xml',['control'=>'jform']);$raw=(array)$this->input->post->get('jform',[],'array');$data=$form->filter($raw);if(!$form->validate($data)){throw new \DomainException(implode(' ',array_map(static fn($error)=>$error instanceof \Throwable?$error->getMessage():(string)$error,$form->getErrors()))?:'Bitte prüfen Sie Ihre Eingaben und das CAPTCHA.');}$this->questions()->send((int)$app->getIdentity()->id,$productId,$data);$app->enqueueMessage('Ihre Produktfrage wurde versendet.');}catch(\Throwable $e){$app->enqueueMessage($e->getMessage(),'error');}
        $app->redirect(Route::_(RouteHelper::getProductRoute($productId,$this->input->post->getInt('catid')),false));
    }

    private function watchlist():WatchlistServiceInterface{return Factory::getApplication()->bootComponent('com_fdshop')->getContainer()->get(WatchlistServiceInterface::class);}
    private function questions():ProductQuestionServiceInterface{return Factory::getApplication()->bootComponent('com_fdshop')->getContainer()->get(ProductQuestionServiceInterface::class);}
}
