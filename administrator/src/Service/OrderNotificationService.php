<?php
namespace FDShop\Component\FDShop\Administrator\Service;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class OrderNotificationService
{
    public function __construct(private readonly DatabaseInterface $db){}
    public function sendForStatus(int $orderId,int $statusId):array
    {
        $order=$this->row('#__fdshop_orders','id='.(int)$orderId);$status=$this->row('#__fdshop_order_statuses','id='.(int)$statusId);
        if(!$order||!$status)return ['Bestell- oder Statusdaten für Benachrichtigung fehlen.'];
        $warnings=[];
        if((int)$status->notify_buyer===1 && (string)$status->buyer_email_mode!=='none')$this->sendOnce($order,$status,'buyer',(string)$order->customer_email,$warnings);
        if((int)$status->notify_seller===1 && (string)$status->seller_email_mode!=='none'){
            $to=(string)$status->seller_email_mode==='custom'?(string)$status->seller_email_address:(string)Factory::getApplication()->get('mailfrom');
            $this->sendOnce($order,$status,'seller',$to,$warnings);
        }
        if((int)$status->create_invoice===1)$this->history($orderId,'invoice_pending','Rechnung vorgemerkt','Für diesen Status ist Rechnungserzeugung konfiguriert; eine Invoice-Engine ist in V1 noch nicht vorhanden.');
        return $warnings;
    }
    private function sendOnce(object $order,object $status,string $kind,string $to,array &$warnings):void
    {
        $event='mail_'.$kind.'_status_'.(int)$status->id;
        $q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__fdshop_order_history'))->where('order_id='.(int)$order->id)->where('event_type='.$this->db->quote($event));$this->db->setQuery($q);if((int)$this->db->loadResult()>0)return;
        if(!filter_var($to,FILTER_VALIDATE_EMAIL)){$warnings[]='Die '.$kind.'-E-Mail-Adresse ist ungültig.';$this->history((int)$order->id,$event.'_failed','E-Mail nicht versendet',end($warnings));return;}
        try{$mailer=Factory::getMailer();$mailer->addRecipient($to);$mailer->setSubject('FDShop Bestellung '.$order->order_number.' – '.$status->status_name);$mailer->isHtml(true);$mailer->setBody('<h1>Bestellung '.htmlspecialchars((string)$order->order_number,ENT_QUOTES,'UTF-8').'</h1><p>Status: '.htmlspecialchars((string)$status->status_name,ENT_QUOTES,'UTF-8').'</p><p>Gesamtbetrag: '.number_format((float)$order->grand_total,2,',','.').' '.htmlspecialchars((string)$order->currency,ENT_QUOTES,'UTF-8').'</p><p>Zahlungsart: '.htmlspecialchars((string)$order->payment_method_name,ENT_QUOTES,'UTF-8').'</p>');$result=$mailer->send();if($result!==true)throw new \RuntimeException('Mailer hat die Nachricht nicht bestätigt.');$this->history((int)$order->id,$event,'E-Mail versendet',$to);}catch(\Throwable $e){$warnings[]='E-Mail konnte nicht versendet werden.';$this->history((int)$order->id,$event.'_failed','E-Mail-Versand fehlgeschlagen',$e->getMessage());}
    }
    private function history(int $orderId,string $type,string $title,?string $text):void{$row=(object)['order_id'=>$orderId,'event_type'=>$type,'event_title'=>$title,'event_text'=>$text,'reference_type'=>'order_status','reference_id'=>null,'is_system_event'=>1,'created'=>Factory::getDate()->toSql(),'created_by'=>(int)Factory::getApplication()->getIdentity()->id];$this->db->insertObject('#__fdshop_order_history',$row);}
    private function row(string $table,string $where):?object{$q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName($table))->where($where);$this->db->setQuery($q);return $this->db->loadObject()?:null;}
}
