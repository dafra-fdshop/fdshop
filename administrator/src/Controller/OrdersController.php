<?php
namespace FDShop\Component\FDShop\Administrator\Controller;
defined('_JEXEC') or die;

use FDShop\Component\FDShop\Administrator\Service\OrderServiceInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;

class OrdersController extends AdminController
{
    protected $text_prefix = 'COM_FDSHOP_ORDERS';
    protected $default_view = 'orders';

    public function getModel($name = 'Orders', $prefix = 'Administrator', $config = ['ignore_request' => true]) { return parent::getModel($name,$prefix,$config); }
    public function save($key = null, $urlVar = null): bool
    {
        if(!$this->authoriseMutation()) return false; $ids=$this->ids(); $status=$this->input->post->getInt('order_status_id');
        if($ids===[] || $status<=0) return $this->fail($ids===[]?'Keine Bestellungen markiert.':'Kein Bestellstatus ausgewählt.');
        try { $changed=0; foreach($ids as $id) if($this->service()->changeStatus($id,$status,'Bulk-Statusänderung aus der Bestellliste')) $changed++; $this->setMessage($changed.' Bestellung(en) aktualisiert.'); }
        catch(\Throwable $e){$this->setMessage($e->getMessage(),'error');}
        $this->setRedirect($this->redirect()); return true;
    }
    public function trashconfirm(): bool
    {
        if(!$this->authoriseMutation()) return false; $ids=$this->ids();
        if($ids===[]) return $this->fail('Keine Bestellungen markiert.');
        if($this->input->post->getInt('confirm_trash')!==1) return $this->fail('Das Verschieben in den Papierkorb wurde nicht bestätigt.');
        return $this->changeState($ids,-2,'Bestellungen wurden in den Papierkorb verschoben.');
    }
    public function restore(): bool
    {
        if(!$this->authoriseMutation()) return false; $ids=$this->ids(); if($ids===[]) return $this->fail('Keine Bestellungen markiert.');
        return $this->changeState($ids,1,'Bestellungen wurden wiederhergestellt.');
    }
    private function changeState(array $ids,int $state,string $message): bool
    {
        try{$this->service()->setOrderState($ids,$state);$this->setMessage($message);}catch(\Throwable $e){$this->setMessage($e->getMessage(),'error');}
        $this->setRedirect($this->redirect()); return true;
    }
    private function authoriseMutation(): bool
    {
        $this->checkToken(); if(!Factory::getApplication()->getIdentity()->authorise('core.manage','com_fdshop')) return $this->fail('Sie sind nicht berechtigt, Bestellungen zu bearbeiten.'); return true;
    }
    private function ids(): array { return array_values(array_unique(array_filter(array_map('intval',$this->input->post->get('cid',[],'array'))))); }
    private function service(): OrderServiceInterface { return Factory::getApplication()->bootComponent('com_fdshop')->getContainer()->get(OrderServiceInterface::class); }
    private function fail(string $message): bool {$this->setMessage($message,'warning');$this->setRedirect($this->redirect());return false;}
    private function redirect(): string {return 'index.php?option=com_fdshop&view=orders';}
}
