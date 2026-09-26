<?php
namespace FDShop\Component\FDShop\Administrator\Service;
defined('_JEXEC') or die;

use Dompdf\Dompdf;
use Dompdf\Options;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class OrderDocumentService
{
    private const CUSTOMER = 'confirmation';
    private const PACKING = 'packing';

    public function __construct(private readonly DatabaseInterface $db) {}

    public function customerDocument(int $orderId, bool $archive = true): array
    {
        $data = $this->data($orderId);
        $filename = 'Bestellbestaetigung_' . $this->safeNumber($data['order']->order_number) . '.pdf';
        if ($archive && !empty($data['order']->confirmation_pdf_path)) {
            $path = $this->archiveRoot() . '/' . basename((string) $data['order']->confirmation_pdf_path);
            if (is_file($path)) return ['name'=>$filename, 'path'=>$path, 'bytes'=>(string) file_get_contents($path), 'archived'=>true];
        }
        $bytes = $this->render($this->customerHtml($data));
        if ($archive) {
            $path = $this->archiveRoot() . '/' . $filename;
            $this->ensureArchiveRoot();
            if (!is_file($path) && file_put_contents($path, $bytes, LOCK_EX) === false) throw new \RuntimeException('Die Bestellbestätigung konnte nicht archiviert werden.');
            $stored = (string) file_get_contents($path);
            $hash = hash('sha256', $stored);
            $query = $this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_orders'))
                ->set($this->db->quoteName('confirmation_pdf_path').'='.$this->db->quote($filename))
                ->set($this->db->quoteName('confirmation_pdf_sha256').'='.$this->db->quote($hash))
                ->where($this->db->quoteName('id').'='.(int)$orderId)
                ->where('('.$this->db->quoteName('confirmation_pdf_path').' IS NULL OR '.$this->db->quoteName('confirmation_pdf_path')."='')");
            $this->db->setQuery($query)->execute();
            return ['name'=>$filename, 'path'=>$path, 'bytes'=>$stored, 'archived'=>true, 'sha256'=>$hash];
        }
        return ['name'=>$filename, 'path'=>null, 'bytes'=>$bytes, 'archived'=>false];
    }

    public function packingList(int $orderId): array
    {
        $data = $this->data($orderId);
        $name = 'Packliste_' . $this->safeNumber($data['order']->order_number) . '.pdf';
        return ['name'=>$name, 'path'=>null, 'bytes'=>$this->render($this->packingHtml($data)), 'archived'=>false];
    }

    public function archivedCustomerDocument(int $orderId): ?array
    {
        $order = $this->row('#__fdshop_orders', 'id='.(int)$orderId);
        if (!$order || empty($order->confirmation_pdf_path)) return null;
        $name = basename((string)$order->confirmation_pdf_path);
        $path = $this->archiveRoot().'/'.$name;
        if (!is_file($path)) return null;
        $bytes = (string)file_get_contents($path);
        if (!empty($order->confirmation_pdf_sha256) && !hash_equals((string)$order->confirmation_pdf_sha256, hash('sha256',$bytes))) throw new \RuntimeException('Die archivierte Bestellbestätigung ist beschädigt.');
        return ['name'=>$name,'path'=>$path,'bytes'=>$bytes,'archived'=>true];
    }

    private function data(int $orderId): array
    {
        $order = $this->row('#__fdshop_orders', 'id='.(int)$orderId);
        if (!$order) throw new \RuntimeException('Bestellung nicht gefunden.');
        $config = $this->row('#__fdshop_config', 'id=1') ?: (object)[];
        $items = $this->list('#__fdshop_order_items', 'order_id='.(int)$orderId.' AND is_removed=0');
        $bundles = $this->list('#__fdshop_order_bundles', 'order_id='.(int)$orderId.' AND is_removed=0');
        $bundleItems=[];
        foreach ($bundles as $bundle) foreach ($this->list('#__fdshop_order_bundle_items','order_bundle_id='.(int)$bundle->id.' AND is_removed=0') as $item) {$item->bundle_name=$bundle->bundle_name;$bundleItems[]=$item;}
        $shipment=$this->row('#__fdshop_shipments','id='.(int)$order->shipment_id);
        return compact('order','config','items','bundles','bundleItems','shipment');
    }

    private function customerHtml(array $d): string
    {
        $o=$d['order'];$c=$d['config'];$rows='';$i=0;
        foreach ($d['items'] as $item) $rows.=$this->customerRow($item,++$i);
        foreach ($d['bundles'] as $bundle) {
            $item=(object)['document_image_path'=>'','sku'=>$bundle->bundle_number,'product_name'=>$bundle->bundle_name,'regular_price_gross'=>$bundle->subtotal_gross,'unit_price_gross'=>$bundle->total_gross,'quantity'=>1,'line_total_gross'=>$bundle->total_gross,'currency'=>$o->currency];
            $rows.=$this->customerRow($item,++$i);
        }
        $sums='<tr><td>Zwischensumme</td><td>'.$this->money($o->subtotal,$o->currency).'</td></tr>';
        if((float)$o->coupon_discount>0)$sums.='<tr><td>Gutschein'.($o->coupon_code?' ('.$this->e($o->coupon_code).')':'').'</td><td>- '.$this->money($o->coupon_discount,$o->currency).'</td></tr>';
        if((float)$o->payment_fee>0)$sums.='<tr><td>Zahlungsgebühr</td><td>'.$this->money($o->payment_fee,$o->currency).'</td></tr>';
        if((float)$o->shipment_fee>0)$sums.='<tr><td>Versand-/Abholgebühr</td><td>'.$this->money($o->shipment_fee,$o->currency).'</td></tr>';
        $payment='';if(preg_match('/überweisung|bank/i',(string)$o->payment_method_name))$payment='<p class="payment">Bitte überweisen Sie den Gesamtbetrag innerhalb von '.max(1,(int)($c->document_payment_days??7)).' Tagen.</p>';
        return $this->head('Bestellbestätigung (Kunde)').'<body>'.$this->documentHeader('Bestellbestätigung (Kunde)',$o,$c,true)
            .'<div class="meta"><b>Bestelldatum:</b> '.$this->date($o->created).'<br><b>Bestellnummer:</b> '.$this->e($o->order_number).'<br><span class="shipment">Abholstation „'.$this->e($o->shipment_name).'“</span></div>'
            .'<table class="items"><thead><tr><th>Bild</th><th>Art.-Nr.</th><th>Produkt</th><th>Einzelpreis</th><th>Menge</th><th>Rabatt</th><th>Betrag</th></tr></thead><tbody>'.$rows.'</tbody></table>'
            .'<table class="sums">'.$sums.'<tr class="total"><td>Gesamtbetrag</td><td>'.$this->money($o->grand_total,$o->currency).'</td></tr></table>'.$payment
            .'<div class="thanks">Vielen Dank für Ihren Einkauf bei '.$this->e($c->document_company_name??'FDShop').'</div>'.$this->footer($c).'</body></html>';
    }

    private function packingHtml(array $d): string
    {
        $o=$d['order'];$c=$d['config'];$groups=[1=>[],2=>[]];
        foreach(array_merge($d['items'],$d['bundleItems']) as $item){$group=(int)($item->packing_group??0);if(!$group)$group=$this->currentPackingGroup((int)$item->product_id,(int)($c->document_special_category_id??0));$groups[$group===2?2:1][]=$item;}
        $color=$this->safeColor((string)($d['shipment']->shipment_color??''));
        $textColor=$this->contrastColor($color);
        $html=$this->head('Packliste').'<body>'.$this->documentHeader('Bestellbestätigung (Firma)',$o,$c,false)
            .'<div class="shipping" style="background:'.$color.';color:'.$textColor.'"><b>Versandinformationen:</b><br>Käufergruppe: <span>________________</span><br>Abholstation „'.$this->e($o->shipment_name).'“<br>☐ Gepackt: Paketanzahl __________</div>'
            .'<div class="remark"><b>Bemerkung Käufer:</b> '.($o->order_note!==null&&trim((string)$o->order_note)!==''?nl2br($this->e($o->order_note)):'Keine Bemerkung').'</div><div class="notes"><b>Notizen:</b></div>';
        foreach([1,2] as $group){$title=$group===1?($c->document_collection_one_title??'Sammlung 1'):($c->document_collection_two_title??'Sammlung 2');$rows='';foreach($groups[$group] as $n=>$item)$rows.=$this->packingRow($item,$n+1,$group);$content=$rows!==''?'<table class="items packing"><thead><tr><th>Bild</th><th>Art.-Nr.</th><th>Produkt</th><th>Kategorie</th><th>Anzahl</th><th>Gerichtet</th><th>Gepackt</th></tr></thead><tbody>'.$rows.'</tbody></table>':'<p class="empty">Keine Positionen</p>';$html.='<section class="collection"><h2>'.$this->e($title).'</h2>'.$content.'</section>';}
        return $html.'</body></html>';
    }

    private function documentHeader(string $title, object $o, object $c, bool $address): string
    {
        $logo=$this->image((string)($c->document_company_logo??''));$company='<div class="company">'.$logo.'<b>'.$this->e($c->document_company_name??'FDShop').'</b><br>'.$this->e($c->document_company_street??'').'<br>'.$this->e(trim(($c->document_company_postal_code??'').' '.($c->document_company_city??''))).'<br><br>'.$this->e($c->document_company_phone??'').'<br>'.$this->e($c->document_company_email??'').'<br>'.$this->e($c->document_company_website??'').'</div>';
        $left='<h1>'.$this->e($title).'</h1>';
        if($address)$left.='<div class="address"><b>Rechnungsadresse:</b><br>'.($o->customer_company?$this->e($o->customer_company).'<br>':'').$this->e(trim($o->customer_first_name.' '.$o->customer_last_name)).'<br>'.$this->e($o->customer_street).'<br>'.$this->e(trim($o->customer_postal_code.' '.$o->customer_city)).($o->customer_country?'<br>'.$this->e($o->customer_country):'').'</div>';
        else $left.='<div class="address">'.($o->customer_company?$this->e($o->customer_company).'<br>':'').$this->e(trim($o->customer_first_name.' '.$o->customer_last_name)).'<br>'.$this->e($o->customer_street).'<br>'.$this->e(trim($o->customer_postal_code.' '.$o->customer_city)).'</div><div class="order-meta">Bestellnummer: '.$this->e($o->order_number).'<br>Bestelldatum: '.$this->date($o->created).'</div>';
        return '<table class="header"><tr><td>'.$left.'</td><td>'.$company.'</td></tr></table>';
    }

    private function customerRow(object $item,int $n):string{$currency=(string)($item->currency??'EUR');$regular=(float)$item->regular_price_gross;$effective=(float)$item->unit_price_gross;$discount=max(0,$regular-$effective);$price=$discount>0?'<s>'.$this->money($regular,$currency).'</s><br>'.$this->money($effective,$currency):$this->money($effective,$currency);return '<tr'.($n%2===0?' class="alt"':'').'><td>'.$this->image((string)($item->document_image_path??''),40).'</td><td>'.$this->e($item->sku).'</td><td>'.$this->e($item->product_name).'</td><td>'.$price.'</td><td>'.$this->qty($item->quantity).'</td><td>'.($discount>0?$this->money($discount,$currency):'-').'</td><td>'.$this->money($item->line_total_gross,$currency).'</td></tr>';}
    private function packingRow(object $item,int $n,int $group):string{return '<tr'.($n%2===0?' class="alt"':'').'><td>'.$this->image((string)($item->document_image_path??''),36).'</td><td>'.$this->e($item->sku).'</td><td>'.$this->e($item->product_name).'</td><td>'.($group===2?'Verbundfeuerwerk':'Alle außer Verbünde').'</td><td>'.$this->qty($item->quantity).'</td><td>☐</td><td>☐</td></tr>';}
    private function render(string $html):string{$autoload=JPATH_ADMINISTRATOR.'/components/com_fdshop/vendor/autoload.php';if(!is_file($autoload))throw new \RuntimeException('PDF-Runtime fehlt.');require_once $autoload;$options=new Options();$options->set('isRemoteEnabled',false);$options->set('isHtml5ParserEnabled',true);$options->set('chroot',JPATH_ROOT);$pdf=new Dompdf($options);$pdf->loadHtml($html,'UTF-8');$pdf->setPaper('A4','portrait');$pdf->render();return $pdf->output();}
    private function head(string $title):string{return '<!doctype html><html lang="de"><head><meta charset="utf-8"><title>'.$this->e($title).'</title><style>@page{margin:16mm 13mm 18mm}body{font-family:DejaVu Sans,sans-serif;font-size:9pt;color:#111}h1{font-size:17pt;font-weight:400;margin:0 0 18mm;white-space:nowrap}h2{font-size:14pt;margin:7mm 0 3mm}.header{width:100%;border-collapse:collapse}.header td{width:50%;vertical-align:top}.company{text-align:right;line-height:1.35;font-size:9pt}.company img{max-width:45mm;max-height:22mm;display:block;margin-left:auto;margin-bottom:3mm}.address{line-height:1.45}.order-meta{margin-top:7mm}.meta{margin:10mm 0 7mm;line-height:1.6}.shipment{display:inline-block;background:#ddd;padding:2mm 4mm;font-size:13pt}.items{width:100%;border-collapse:collapse;table-layout:fixed}.items thead{display:table-header-group}.items tr{page-break-inside:avoid}.items th,.items td{padding:2.2mm 1.5mm;border-bottom:.2mm solid #ccc;text-align:left;vertical-align:middle}.items .alt{background:#eef2f8}.items img{max-width:12mm;max-height:12mm}.sums{width:45%;margin:6mm 0 0 auto;border-collapse:collapse}.sums td{padding:1.3mm}.sums td:last-child{text-align:right}.sums .total{font-weight:bold;font-size:12pt;border-top:.4mm solid #333}.payment{text-align:right}.thanks{text-align:center;font-weight:bold;margin-top:30mm;border-bottom:.3mm solid #aaa;padding-bottom:3mm}.footer{font-size:7pt;margin-top:3mm}.footer td{vertical-align:top}.shipping{margin:5mm 0;padding:4mm;font-size:10pt;line-height:1.45}.remark,.notes{border:.3mm solid #999;padding:3mm;margin:4mm 0}.notes{height:18mm}.collection{page-break-inside:auto}.packing th,.packing td{font-size:8pt}.empty{border-bottom:.2mm solid #ccc;padding:2mm}.paper{color:#555}</style></head>';}
    private function footer(object $c):string{return '<table class="footer" width="100%"><tr><td><b>Bankverbindung:</b><br>Kontoinhaber: '.$this->e($c->document_account_holder??'').'<br>Bankname: '.$this->e($c->document_bank_name??'').'</td><td>IBAN: '.$this->e($c->document_iban??'').'<br>BIC/SWIFT: '.$this->e($c->document_bic??'').'</td><td>'.$this->e(strip_tags((string)($c->document_footer_text??''))).'</td></tr></table>';}
    private function image(string $path,int $size=0):string{$path=trim($path);if($path==='')return '';$full=realpath(JPATH_ROOT.'/'.ltrim($path,'/'));$root=realpath(JPATH_ROOT);if(!$full||!$root||!str_starts_with($full,$root.DIRECTORY_SEPARATOR)||!is_file($full))return '';$mime=mime_content_type($full);if(!in_array($mime,['image/jpeg','image/png','image/webp','image/gif'],true))return '';$style=$size?' style="max-width:'.$size.'px;max-height:'.$size.'px"':'';return '<img'.$style.' src="data:'.$mime.';base64,'.base64_encode((string)file_get_contents($full)).'">';}
    private function currentPackingGroup(int $productId,int $special):int{if($special<=0)return 1;$q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__fdshop_product_category_map'))->where('product_id='.$productId)->where('category_id='.$special);$this->db->setQuery($q);return (int)$this->db->loadResult()>0?2:1;}
    private function safeColor(string $color):string{return preg_match('/^#[0-9a-f]{6}$/i',$color)?$color:'#d9dde2';}
    private function contrastColor(string $color):string{$r=hexdec(substr($color,1,2));$g=hexdec(substr($color,3,2));$b=hexdec(substr($color,5,2));return (($r*299+$g*587+$b*114)/1000)<128?'#ffffff':'#111111';}
    private function safeNumber(string $number):string{$safe=preg_replace('/[^A-Za-z0-9_-]/','',$number);if($safe===''||$safe!==$number)throw new \RuntimeException('Ungültige Bestellnummer für Dokumentdatei.');return $safe;}
    private function archiveRoot():string{return JPATH_ADMINISTRATOR.'/components/com_fdshop/documents';}
    private function ensureArchiveRoot():void{$dir=$this->archiveRoot();if(!is_dir($dir)&&!mkdir($dir,0750,true)&&!is_dir($dir))throw new \RuntimeException('Geschütztes Dokumentverzeichnis konnte nicht angelegt werden.');$deny=$dir.'/.htaccess';if(!is_file($deny))file_put_contents($deny,"Require all denied\nDeny from all\n");$index=$dir.'/index.html';if(!is_file($index))file_put_contents($index,'');}
    private function row(string $table,string $where):?object{$q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName($table))->where($where);$this->db->setQuery($q);return $this->db->loadObject()?:null;}
    private function list(string $table,string $where):array{$q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName($table))->where($where)->order('id ASC');$this->db->setQuery($q);return (array)$this->db->loadObjectList();}
    private function e(mixed $value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    private function money(mixed $value,string $currency):string{return number_format((float)$value,2,',','.').' '.$this->e($currency?:'EUR');}
    private function qty(mixed $value):string{$n=(float)$value;return abs($n-round($n))<.0001?(string)(int)round($n):number_format($n,3,',','.');}
    private function date(string $date):string{return Factory::getDate($date)->format('d.m.Y H:i',true);}
}
