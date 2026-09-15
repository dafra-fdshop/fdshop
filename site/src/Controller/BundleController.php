<?php

namespace FDShop\Component\FDShop\Site\Controller;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Service\BundleServiceInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

final class BundleController extends BaseController
{
    public function builder(): void { $this->respond(false, fn($s,$u,$session)=>$s->getBuilder((int)$this->input->getInt('bundle_id'),$u,(int)$this->input->getInt('saved_bundle_id'),(int)$this->input->getInt('cart_bundle_id'),$session)); }
    public function calculate(): void { $this->respond(true, fn($s,$u,$session)=>$s->calculate((int)$this->input->post->getInt('bundle_id'),$this->items(),$u,$session,(int)$this->input->post->getInt('cart_bundle_id'))); }
    public function save(): void { $this->respond(true, function($s,$u,$session){$id=$s->save($u,(int)$this->input->post->getInt('bundle_id'),(string)$this->input->post->getString('saved_name'),$this->items(),(int)$this->input->post->getInt('saved_bundle_id'));return ['saved_bundle_id'=>$id];}, 'Bundle wurde gespeichert.'); }
    public function deleteSaved(): void { $this->respond(true, function($s,$u){$s->deleteSaved($u,$this->input->post->getInt('saved_bundle_id'));return [];}, 'Gespeichertes Bundle wurde gelöscht.'); }
    public function addToCart(): void { $this->respond(true, function($s,$u,$session){$id=$s->addToCart($u,$session,(int)$this->input->post->getInt('bundle_id'),$this->items(),(int)$this->input->post->getInt('cart_bundle_id'));return ['cart_bundle_id'=>$id];}, 'Bundle wurde in den Warenkorb gelegt.'); }
    public function removeFromCart(): void { $this->respond(true, function($s,$u,$session){$s->removeFromCart($u,$session,$this->input->post->getInt('cart_bundle_id'));return [];}, 'Bundle wurde aus dem Warenkorb entfernt.'); }

    private function items(): array { $raw=$this->input->post->getString('items','{}'); $items=json_decode($raw,true); if(!is_array($items))throw new \DomainException('Die Bundle-Auswahl ist ungültig.'); return $items; }
    private function service(): BundleServiceInterface { return Factory::getApplication()->bootComponent('com_fdshop')->getContainer()->get(BundleServiceInterface::class); }
    private function respond(bool $token, callable $callback, string $message=''): void
    {
        $app=Factory::getApplication();
        try { if($token&&!Session::checkToken('request'))throw new \RuntimeException('Ungültiger Sicherheitstoken.'); $userId=(int)$app->getIdentity()->id; $data=$callback($this->service(),$userId,$app->getSession()->getId()); echo new JsonResponse($this->normalise($data),$message); }
        catch(\Throwable $e){ echo new JsonResponse(null,$e->getMessage(),true); }
        $app->close();
    }
    private function normalise(mixed $value): mixed { if(is_object($value))$value=get_object_vars($value); if(is_array($value)){foreach($value as $key=>$item)$value[$key]=$this->normalise($item);} return $value; }
}
