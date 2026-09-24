<?php
declare(strict_types=1);
namespace FDShop\Component\FDShop\Administrator\Controller;
defined('_JEXEC') or die;
use FDShop\Component\FDShop\Administrator\Service\MasterImageImportService;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

final class MasterimageimportController extends BaseController
{
    public function upload(): void {$this->guard();try{$r=$this->service()->stageUploads($_FILES['masters']??[]);$ok=count(array_filter($r,fn($x)=>in_array($x['status'],['STAGED','ALREADY_STAGED'],true)));$this->app->enqueueMessage($ok.' Masterdatei(en) bereitgestellt.');foreach($r as $x)if(!in_array($x['status'],['STAGED','ALREADY_STAGED'],true))$this->app->enqueueMessage($x['filename'].': '.$x['message'],'warning');}catch(\Throwable $e){$this->app->enqueueMessage($e->getMessage(),'error');}$this->setRedirect(Route::_('index.php?option=com_fdshop&view=masterimageimport',false));$this->redirect();}
    public function cleanup(): void {$this->guard();try{$n=$this->service()->clearStaging();$this->app->enqueueMessage($n.' Stagingdatei(en) entfernt. Importierte Produktbilder und Import-State bleiben erhalten.');}catch(\Throwable $e){$this->app->enqueueMessage($e->getMessage(),'error');}$this->setRedirect(Route::_('index.php?option=com_fdshop&view=masterimageimport',false));$this->redirect();}
    public function startBatch(): void {$this->guardJson();try{$batch=$this->service()->createBatch($this->input->post->get('skus',[],'array'),(int)$this->app->getIdentity()->id);$this->json(['batch_id'=>$batch['id'],'total'=>count($batch['items']),'chunk_size'=>MasterImageImportService::CHUNK_SIZE]);}catch(\Throwable $e){$this->json(null,$e->getMessage(),true);}}
    public function processBatch(): void {$this->guardJson();try{$this->json($this->service()->processBatch($this->input->post->getString('batch_id'),(int)$this->app->getIdentity()->id));}catch(\Throwable $e){$this->json(null,$e->getMessage(),true);}}
    private function guard(): void {if($this->input->getMethod()!=='POST'||!$this->app->isClient('administrator')||!$this->app->getIdentity()->authorise('core.manage','com_fdshop'))throw new \RuntimeException('Keine Berechtigung.');$this->checkToken();}
    private function guardJson(): void {if($this->input->getMethod()!=='POST'||!$this->app->isClient('administrator')||!$this->app->getIdentity()->authorise('core.manage','com_fdshop')||!Session::checkToken('request')){$this->json(null,'Keine Berechtigung oder ungültiger Token.',true);}}
    private function service(): MasterImageImportService {return Factory::getApplication()->bootComponent('com_fdshop')->getContainer()->get(MasterImageImportService::class);}
    private function json(mixed $data,string $message='',bool $error=false): never {echo new JsonResponse($data,$message,$error);$this->app->close();}
}
