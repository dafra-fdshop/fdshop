<?php
namespace FDShop\Component\FDShop\Administrator\Service;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class OrderNotificationService
{
    public function __construct(private readonly DatabaseInterface $db,private readonly OrderDocumentService $documents){}
    public function sendForStatus(int $orderId,int $statusId):array
    {
        $order=$this->row('#__fdshop_orders','id='.(int)$orderId);$status=$this->row('#__fdshop_order_statuses','id='.(int)$statusId);if(!$order||!$status)return ['Bestell- oder Statusdaten für Benachrichtigung fehlen.'];$warnings=[];
        if((int)$status->notify_buyer===1&&(string)$status->buyer_email_mode!=='none')$this->sendStatusOnce($order,$status,'buyer',(string)$order->customer_email,$warnings);
        if((int)$status->notify_seller===1&&(string)$status->seller_email_mode!=='none'){$to=(string)$status->seller_email_mode==='custom'?(string)$status->seller_email_address:(string)Factory::getApplication()->get('mailfrom');$this->sendStatusOnce($order,$status,'seller',$to,$warnings);}
        if((int)$status->create_invoice===1)$this->history($orderId,'invoice_pending','Rechnung vorgemerkt','Für diesen Status ist Rechnungserzeugung konfiguriert; eine Invoice-Engine ist in V1 noch nicht vorhanden.');return $warnings;
    }
    public function sendOrderChanged(int $orderId,int $changeId):array
    {
        $order=$this->row('#__fdshop_orders','id='.(int)$orderId);if(!$order)return ['Bestelldaten für Änderungsbestätigung fehlen.'];$warnings=[];
        $this->sendChangeOnce($order,$changeId,'buyer',(string)$order->customer_email,$warnings);
        $status=$this->row('#__fdshop_order_statuses','id='.(int)$order->order_status_id);$to=$status&&(string)$status->seller_email_mode==='custom'?(string)$status->seller_email_address:(string)Factory::getApplication()->get('mailfrom');
        $this->sendChangeOnce($order,$changeId,'seller',$to,$warnings);return $warnings;
    }
    private function sendStatusOnce(object $order,object $status,string $kind,string $to,array &$warnings):void
    {
        $event='mail_'.$kind.'_status_'.(int)$status->id;if($this->sent((int)$order->id,$event,null))return;if(!filter_var($to,FILTER_VALIDATE_EMAIL)){$warnings[]='Die '.($kind==='buyer'?'Käufer':'Verkäufer').'-E-Mail-Adresse ist ungültig.';$this->history((int)$order->id,$event.'_failed','E-Mail nicht versendet',end($warnings));return;}$temporary=null;
        try{$mailer=Factory::getMailer();$mailer->addRecipient($to);$mailer->setSubject('FDShop Bestellbestätigung '.$order->order_number);$mailer->isHtml(true);$mailer->setBody($this->documents->mailBody((int)$order->id,false,$kind==='seller'));
            if((string)$status->status_code==='ordered'){$document=$kind==='buyer'?$this->documents->customerDocument((int)$order->id,true):$this->documents->packingList((int)$order->id);$this->attach($mailer,$document,$temporary);}
            if($mailer->send()!==true)throw new \RuntimeException('Mailer hat die Nachricht nicht bestätigt.');$this->history((int)$order->id,$event,'E-Mail versendet',$to);
        }catch(\Throwable $e){$warnings[]='E-Mail oder Bestelldokument konnte nicht versendet werden.';$this->history((int)$order->id,$event.'_failed','E-Mail-/Dokumentversand fehlgeschlagen',$e->getMessage());}finally{if($temporary&&is_file($temporary))unlink($temporary);}
    }
    private function sendChangeOnce(object $order,int $changeId,string $kind,string $to,array &$warnings):void
    {
        $event='mail_'.$kind.'_order_changed';if($this->sent((int)$order->id,$event,$changeId))return;if(!filter_var($to,FILTER_VALIDATE_EMAIL)){$warnings[]='Die '.($kind==='buyer'?'Käufer':'Verkäufer').'-E-Mail-Adresse ist ungültig.';$this->history((int)$order->id,$event.'_failed','Änderungsbestätigung nicht versendet',end($warnings),'order_history',$changeId);return;}$temporary=null;
        try{$document=$kind==='buyer'?$this->documents->customerDocument((int)$order->id,true,$changeId):$this->documents->packingList((int)$order->id);$mailer=Factory::getMailer();$mailer->addRecipient($to);$mailer->setSubject('FDShop Bestellung '.$order->order_number.' wurde geändert');$mailer->isHtml(true);$mailer->setBody($this->documents->mailBody((int)$order->id,true,$kind==='seller'));$this->attach($mailer,$document,$temporary);if($mailer->send()!==true)throw new \RuntimeException('Mailer hat die Nachricht nicht bestätigt.');$this->history((int)$order->id,$event,'Änderungsbestätigung versendet',$to,'order_history',$changeId);
        }catch(\Throwable $e){$warnings[]='Änderungsbestätigung konnte nicht versendet werden.';$this->history((int)$order->id,$event.'_failed','Änderungsbestätigung fehlgeschlagen',$e->getMessage(),'order_history',$changeId);}finally{if($temporary&&is_file($temporary))unlink($temporary);}
    }
    private function attach(object $mailer,array $document,?string &$temporary):void{if(!empty($document['path'])){$mailer->addAttachment($document['path'],$document['name']);return;}$temporary=tempnam(JPATH_CACHE,'fdshop-doc-');if($temporary===false||file_put_contents($temporary,$document['bytes'])===false)throw new \RuntimeException('Temporäres Bestelldokument konnte nicht erzeugt werden.');$mailer->addAttachment($temporary,$document['name']);}
    private function sent(int $orderId,string $event,?int $referenceId):bool{$q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__fdshop_order_history'))->where('order_id='.$orderId)->where('event_type='.$this->db->quote($event));if($referenceId!==null)$q->where('reference_id='.$referenceId);$this->db->setQuery($q);return (int)$this->db->loadResult()>0;}
    private function history(int $orderId,string $type,string $title,?string $text,string $referenceType='order_status',?int $referenceId=null):void{$row=(object)['order_id'=>$orderId,'event_type'=>$type,'event_title'=>$title,'event_text'=>$text,'reference_type'=>$referenceType,'reference_id'=>$referenceId,'is_system_event'=>1,'created'=>Factory::getDate()->toSql(),'created_by'=>(int)(Factory::getApplication()->getIdentity()?->id??0)];$this->db->insertObject('#__fdshop_order_history',$row);}
    private function row(string $table,string $where):?object{$q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName($table))->where($where);$this->db->setQuery($q);return $this->db->loadObject()?:null;}
}
