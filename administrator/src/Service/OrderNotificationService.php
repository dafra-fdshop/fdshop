<?php
namespace FDShop\Component\FDShop\Administrator\Service;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class OrderNotificationService
{
    public function __construct(private readonly DatabaseInterface $db, private readonly OrderDocumentService $documents){}
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
    public function sendOrderChanged(int $orderId, int $changeId): array
    {
        $order=$this->row('#__fdshop_orders','id='.(int)$orderId);
        if(!$order)return ['Bestelldaten für Änderungsbestätigung fehlen.'];
        $event='mail_buyer_order_changed';
        $q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__fdshop_order_history'))->where('order_id='.(int)$orderId)->where('event_type='.$this->db->quote($event))->where('reference_id='.(int)$changeId);
        $this->db->setQuery($q);if((int)$this->db->loadResult()>0)return [];
        $to=(string)$order->customer_email;
        if(!filter_var($to,FILTER_VALIDATE_EMAIL)){$warning='Die Käufer-E-Mail-Adresse ist ungültig.';$this->history($orderId,$event.'_failed','Änderungsbestätigung nicht versendet',$warning,'order_history',$changeId);return [$warning];}
        $q=$this->db->getQuery(true)->select(['product_name','sku','quantity','line_total_gross'])->from($this->db->quoteName('#__fdshop_order_items'))->where('order_id='.(int)$orderId)->where('is_removed=0')->order('id ASC');$this->db->setQuery($q);$items=(array)$this->db->loadObjectList();
        $lines='';foreach($items as $item){$lines.='<li>'.htmlspecialchars((string)$item->product_name,ENT_QUOTES,'UTF-8').' ('.htmlspecialchars((string)$item->sku,ENT_QUOTES,'UTF-8').') – '.htmlspecialchars((string)$item->quantity,ENT_QUOTES,'UTF-8').' × '.number_format((float)$item->line_total_gross/max(0.001,(float)$item->quantity),2,',','.').' '.htmlspecialchars((string)$order->currency,ENT_QUOTES,'UTF-8').'</li>';}
        $body='<h1>Bestellung '.htmlspecialchars((string)$order->order_number,ENT_QUOTES,'UTF-8').' wurde geändert</h1><p>Status: '.htmlspecialchars((string)$order->order_status,ENT_QUOTES,'UTF-8').'</p><ul>'.$lines.'</ul><p>Abholung/Versand: '.htmlspecialchars((string)$order->shipment_name,ENT_QUOTES,'UTF-8').' ('.number_format((float)$order->shipment_fee,2,',','.').' '.htmlspecialchars((string)$order->currency,ENT_QUOTES,'UTF-8').')</p><p>Zahlungsart: '.htmlspecialchars((string)$order->payment_method_name,ENT_QUOTES,'UTF-8').'</p><p>Gesamtbetrag: <strong>'.number_format((float)$order->grand_total,2,',','.').' '.htmlspecialchars((string)$order->currency,ENT_QUOTES,'UTF-8').'</strong></p>';
        if(trim((string)$order->order_note)!=='')$body.='<p>Bemerkung: '.nl2br(htmlspecialchars((string)$order->order_note,ENT_QUOTES,'UTF-8')).'</p>';
        try{$mailer=Factory::getMailer();$mailer->addRecipient($to);$mailer->setSubject('FDShop Bestellung '.$order->order_number.' wurde geändert');$mailer->isHtml(true);$mailer->setBody($body);if($mailer->send()!==true)throw new \RuntimeException('Mailer hat die Nachricht nicht bestätigt.');$this->history($orderId,$event,'Änderungsbestätigung versendet',$to,'order_history',$changeId);return [];}catch(\Throwable $e){$warning='Änderungsbestätigung konnte nicht versendet werden.';$this->history($orderId,$event.'_failed','Änderungsbestätigung fehlgeschlagen',$e->getMessage(),'order_history',$changeId);return [$warning];}
    }
    private function sendOnce(object $order,object $status,string $kind,string $to,array &$warnings):void
    {
        $event='mail_'.$kind.'_status_'.(int)$status->id;
        $q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__fdshop_order_history'))->where('order_id='.(int)$order->id)->where('event_type='.$this->db->quote($event));$this->db->setQuery($q);if((int)$this->db->loadResult()>0)return;
        if(!filter_var($to,FILTER_VALIDATE_EMAIL)){$warnings[]='Die '.$kind.'-E-Mail-Adresse ist ungültig.';$this->history((int)$order->id,$event.'_failed','E-Mail nicht versendet',end($warnings));return;}
        $temporary=null;
        try{
            $mailer=Factory::getMailer();$mailer->addRecipient($to);$mailer->setSubject('FDShop Bestellbestätigung '.$order->order_number);$mailer->isHtml(true);$mailer->setBody($this->orderedBody($order));
            if((string)$status->status_code==='ordered'){
                $document=$kind==='buyer'?$this->documents->customerDocument((int)$order->id,true):$this->documents->packingList((int)$order->id);
                if($kind==='seller'){$temporary=tempnam(JPATH_CACHE,'fdshop-pack-');if($temporary===false||file_put_contents($temporary,$document['bytes'])===false)throw new \RuntimeException('Temporäre Packliste konnte nicht erzeugt werden.');$mailer->addAttachment($temporary,$document['name']);}
                else $mailer->addAttachment($document['path'],$document['name']);
            }
            $result=$mailer->send();if($result!==true)throw new \RuntimeException('Mailer hat die Nachricht nicht bestätigt.');$this->history((int)$order->id,$event,'E-Mail versendet',$to);
        }catch(\Throwable $e){$warnings[]='E-Mail oder Bestelldokument konnte nicht versendet werden.';$this->history((int)$order->id,$event.'_failed','E-Mail-/Dokumentversand fehlgeschlagen',$e->getMessage());}
        finally{if($temporary&&is_file($temporary))unlink($temporary);}
    }
    private function orderedBody(object $order):string
    {
        $e=static fn(mixed $v):string=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        return '<div style="font-family:Arial,sans-serif;max-width:680px;margin:auto"><h2 style="background:#111827;color:#fff;padding:18px">FDShop</h2><h3>Bestellbestätigung zu Ihrer Bestellung '.$e($order->order_number).'.</h3><p>Vielen Dank für Ihre Bestellung! Die Bestellbestätigung haben wir dieser E-Mail als PDF beigefügt.</p><table style="width:100%;border-collapse:collapse"><tr><th style="text-align:left;padding:8px;border-bottom:1px solid #ddd">Bestelldatum</th><td style="padding:8px;border-bottom:1px solid #ddd">'.$e(Factory::getDate($order->created)->format('d.m.Y H:i',true)).'</td></tr><tr><th style="text-align:left;padding:8px;border-bottom:1px solid #ddd">Gesamtsumme</th><td style="padding:8px;border-bottom:1px solid #ddd">'.number_format((float)$order->grand_total,2,',','.').' '.$e($order->currency).'</td></tr><tr><th style="text-align:left;padding:8px;border-bottom:1px solid #ddd">Abholstation/Versandart</th><td style="padding:8px;border-bottom:1px solid #ddd">'.$e($order->shipment_name).'</td></tr></table><p style="color:#666;margin-top:28px">Bei Fragen antworten Sie einfach auf diese E-Mail. Vielen Dank für Ihren Einkauf!</p></div>';
    }
    private function history(int $orderId,string $type,string $title,?string $text, string $referenceType='order_status', ?int $referenceId=null):void{$row=(object)['order_id'=>$orderId,'event_type'=>$type,'event_title'=>$title,'event_text'=>$text,'reference_type'=>$referenceType,'reference_id'=>$referenceId,'is_system_event'=>1,'created'=>Factory::getDate()->toSql(),'created_by'=>(int)Factory::getApplication()->getIdentity()->id];$this->db->insertObject('#__fdshop_order_history',$row);}
    private function row(string $table,string $where):?object{$q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName($table))->where($where);$this->db->setQuery($q);return $this->db->loadObject()?:null;}
}
