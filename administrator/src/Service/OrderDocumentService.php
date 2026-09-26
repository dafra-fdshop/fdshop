<?php
namespace FDShop\Component\FDShop\Administrator\Service;
defined('_JEXEC') or die;

use Dompdf\Dompdf;
use Dompdf\Options;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\Database\DatabaseInterface;

final class OrderDocumentService
{
    public function __construct(private readonly DatabaseInterface $db) {}

    public function customerDocument(int $orderId, bool $archive = true, ?int $changeId = null): array
    {
        $this->migrateLegacyDocument($orderId);
        $version=$changeId===null?0:$this->versionForChange($orderId,$changeId);
        if($archive&&($existing=$this->documentRow($orderId,'confirmation',$version)))return $this->readArchived($existing);
        $data=$this->viewData($orderId);$number=$this->safeNumber((string)$data['order']->order_number);
        $filename='Bestellbestaetigung_'.$number.($version>0?'_'.sprintf('%02d',$version):'').'.pdf';
        $bytes=$this->render($this->layout('orderdocuments.customer',$data));
        if(!$archive)return ['name'=>$filename,'path'=>null,'bytes'=>$bytes,'archived'=>false];
        $this->ensureArchiveRoot();$path=$this->archiveRoot().'/'.$filename;
        if(!is_file($path)&&file_put_contents($path,$bytes,LOCK_EX)===false)throw new \RuntimeException('Die Bestellbestätigung konnte nicht archiviert werden.');
        $stored=(string)file_get_contents($path);$hash=hash('sha256',$stored);
        $row=(object)['order_id'=>$orderId,'change_id'=>$changeId,'document_type'=>'confirmation','version_no'=>$version,'filename'=>$filename,'sha256'=>$hash,'created'=>Factory::getDate()->toSql()];
        try{$this->db->insertObject('#__fdshop_order_documents',$row);$documentId=(int)$this->db->insertid();}catch(\Throwable){$existing=$this->documentRow($orderId,'confirmation',$version);if(!$existing)throw new \RuntimeException('Die Dokumentversion konnte nicht gespeichert werden.');return $this->readArchived($existing);}
        if($version===0){$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_orders'))->set('confirmation_pdf_path='.$this->db->quote($filename))->set('confirmation_pdf_sha256='.$this->db->quote($hash))->where('id='.(int)$orderId);$this->db->setQuery($q)->execute();}
        return ['id'=>$documentId,'name'=>$filename,'path'=>$path,'bytes'=>$stored,'archived'=>true,'sha256'=>$hash,'version'=>$version];
    }

    public function packingList(int $orderId):array{$d=$this->viewData($orderId);$name='Packliste_'.$this->safeNumber((string)$d['order']->order_number).'.pdf';return ['name'=>$name,'path'=>null,'bytes'=>$this->render($this->layout('orderdocuments.packing',$d)),'archived'=>false];}
    public function mailBody(int $orderId,bool $changed,bool $seller):string{return $this->layout('orderdocuments.mail',$this->viewData($orderId)+['changed'=>$changed,'seller'=>$seller]);}
    public function archivedCustomerDocument(int $orderId,?int $documentId=null):?array{$this->migrateLegacyDocument($orderId);$q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_order_documents'))->where('order_id='.(int)$orderId)->where('document_type='.$this->db->quote('confirmation'));if($documentId!==null)$q->where('id='.(int)$documentId);$q->order('version_no '.($documentId===null?'ASC':'DESC'));$this->db->setQuery($q,0,1);$row=$this->db->loadObject();return $row?$this->readArchived($row):null;}
    public function customerDocuments(int $orderId):array{$this->migrateLegacyDocument($orderId);$q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_order_documents'))->where('order_id='.(int)$orderId)->where('document_type='.$this->db->quote('confirmation'))->order('version_no ASC');$this->db->setQuery($q);return (array)$this->db->loadObjectList();}

    public function viewData(int $orderId):array
    {
        $order=$this->row('#__fdshop_orders','id='.(int)$orderId);if(!$order)throw new \RuntimeException('Bestellung nicht gefunden.');$config=$this->row('#__fdshop_config','id=1')?:(object)[];
        $items=$this->list('#__fdshop_order_items','order_id='.(int)$orderId.' AND is_removed=0');$bundles=$this->list('#__fdshop_order_bundles','order_id='.(int)$orderId.' AND is_removed=0');$bundleItems=[];
        foreach($bundles as $bundle)foreach($this->list('#__fdshop_order_bundle_items','order_bundle_id='.(int)$bundle->id.' AND is_removed=0') as $item){$item->bundle_name=$bundle->bundle_name;$bundleItems[]=$item;}
        $shipment=$this->row('#__fdshop_shipments','id='.(int)$order->shipment_id);return compact('order','config','items','bundles','bundleItems','shipment')+['helper'=>$this];
    }
    public function image(string $path,int $size=0):string{$clean=HTMLHelper::cleanImageURL(trim($path));$path=trim((string)($clean->url??''));if($path==='')return '';$full=realpath(JPATH_ROOT.'/'.ltrim($path,'/'));$root=realpath(JPATH_ROOT);if(!$full||!$root||!str_starts_with($full,$root.DIRECTORY_SEPARATOR)||!is_file($full))return '';$mime=mime_content_type($full);if(!in_array($mime,['image/jpeg','image/png','image/webp','image/gif'],true))return '';$style=$size?' style="max-width:'.$size.'px;max-height:'.$size.'px"':'';return '<img'.$style.' src="data:'.$mime.';base64,'.base64_encode((string)file_get_contents($full)).'">';}
    public function e(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    public function money(mixed $v,string $c):string{return number_format((float)$v,2,',','.').' '.$this->e($c?:'EUR');}
    public function qty(mixed $v):string{$n=(float)$v;return abs($n-round($n))<.0001?(string)(int)round($n):number_format($n,3,',','.');}
    public function date(string $v):string{return Factory::getDate($v)->format('d.m.Y H:i',true);}
    public function addressLine(string $postal,string $city):string{$postal=trim($postal);$city=trim($city);return $postal!==''&&preg_match('/^'.preg_quote($postal,'/').'(?:\s|$)/u',$city)?$city:trim($postal.' '.$city);}
    public function safeColor(string $c):string{return preg_match('/^#[0-9a-f]{6}$/i',$c)?$c:'#d9dde2';}
    public function contrastColor(string $c):string{$r=hexdec(substr($c,1,2));$g=hexdec(substr($c,3,2));$b=hexdec(substr($c,5,2));return (($r*299+$g*587+$b*114)/1000)<128?'#fff':'#111';}
    public function currentPackingGroup(int $productId,int $special):int{if($special<=0)return 1;$q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__fdshop_product_category_map'))->where('product_id='.$productId)->where('category_id='.$special);$this->db->setQuery($q);return (int)$this->db->loadResult()>0?2:1;}

    private function versionForChange(int $orderId,int $changeId):int{$q=$this->db->getQuery(true)->select('version_no')->from($this->db->quoteName('#__fdshop_order_documents'))->where('order_id='.(int)$orderId)->where('document_type='.$this->db->quote('confirmation'))->where('change_id='.(int)$changeId);$this->db->setQuery($q);$v=$this->db->loadResult();if($v!==null)return (int)$v;$q=$this->db->getQuery(true)->select('COALESCE(MAX(version_no),0)+1')->from($this->db->quoteName('#__fdshop_order_documents'))->where('order_id='.(int)$orderId)->where('document_type='.$this->db->quote('confirmation'));$this->db->setQuery($q);return max(1,(int)$this->db->loadResult());}
    private function readArchived(object $r):array{$name=basename((string)$r->filename);$path=$this->archiveRoot().'/'.$name;if(!is_file($path))throw new \RuntimeException('Die archivierte Bestellbestätigung fehlt.');$bytes=(string)file_get_contents($path);if(!hash_equals((string)$r->sha256,hash('sha256',$bytes)))throw new \RuntimeException('Die archivierte Bestellbestätigung ist beschädigt.');return ['id'=>(int)$r->id,'name'=>$name,'path'=>$path,'bytes'=>$bytes,'archived'=>true,'sha256'=>(string)$r->sha256,'version'=>(int)$r->version_no];}
    private function migrateLegacyDocument(int $orderId):void{$o=$this->row('#__fdshop_orders','id='.(int)$orderId);if(!$o||empty($o->confirmation_pdf_path)||$this->documentRow($orderId,'confirmation',0))return;$name=basename((string)$o->confirmation_pdf_path);$old=JPATH_ADMINISTRATOR.'/components/com_fdshop/documents/'.$name;$new=$this->archiveRoot().'/'.$name;if(!is_file($new)&&is_file($old)){$this->ensureArchiveRoot();if(!copy($old,$new))throw new \RuntimeException('Das bestehende Bestelldokument konnte nicht migriert werden.');}if(!is_file($new))return;$hash=hash_file('sha256',$new);if(!empty($o->confirmation_pdf_sha256)&&!hash_equals((string)$o->confirmation_pdf_sha256,$hash))throw new \RuntimeException('Das bestehende Bestelldokument hat eine unerwartete Prüfsumme.');$r=(object)['order_id'=>$orderId,'change_id'=>null,'document_type'=>'confirmation','version_no'=>0,'filename'=>$name,'sha256'=>$hash,'created'=>Factory::getDate()->toSql()];try{$this->db->insertObject('#__fdshop_order_documents',$r);}catch(\Throwable){}}
    private function documentRow(int $o,string $t,int $v):?object{$q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_order_documents'))->where('order_id='.(int)$o)->where('document_type='.$this->db->quote($t))->where('version_no='.(int)$v);$this->db->setQuery($q);return $this->db->loadObject()?:null;}
    private function layout(string $n,array $d):string{return (new FileLayout($n,JPATH_ADMINISTRATOR.'/components/com_fdshop/layouts',['template'=>'atum','client'=>1,'component'=>'com_fdshop']))->render($d);}
    private function render(string $h):string{$a=JPATH_ADMINISTRATOR.'/components/com_fdshop/vendor/autoload.php';if(!is_file($a))throw new \RuntimeException('PDF-Runtime fehlt.');require_once $a;$o=new Options();$o->set('isRemoteEnabled',false);$o->set('isHtml5ParserEnabled',true);$o->set('chroot',JPATH_ROOT);$p=new Dompdf($o);$p->loadHtml($h,'UTF-8');$p->setPaper('A4','portrait');$p->render();return $p->output();}
    private function archiveRoot():string{return JPATH_ROOT.'/media/com_fdshop-private/documents';}
    private function ensureArchiveRoot():void{$d=$this->archiveRoot();if(!is_dir($d)&&!mkdir($d,0750,true)&&!is_dir($d))throw new \RuntimeException('Geschütztes Dokumentverzeichnis konnte nicht angelegt werden.');if(!is_file($d.'/.htaccess'))file_put_contents($d.'/.htaccess',"Require all denied\nDeny from all\n");if(!is_file($d.'/index.html'))file_put_contents($d.'/index.html','');}
    private function safeNumber(string $n):string{$s=preg_replace('/[^A-Za-z0-9_-]/','',$n);if($s===''||$s!==$n)throw new \RuntimeException('Ungültige Bestellnummer für Dokumentdatei.');return $s;}
    private function row(string $t,string $w):?object{$q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName($t))->where($w);$this->db->setQuery($q);return $this->db->loadObject()?:null;}
    private function list(string $t,string $w):array{$q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName($t))->where($w)->order('id ASC');$this->db->setQuery($q);return (array)$this->db->loadObjectList();}
}
