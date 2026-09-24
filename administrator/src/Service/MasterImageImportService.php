<?php
declare(strict_types=1);

namespace FDShop\Component\FDShop\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use RuntimeException;
use Throwable;
use ZipArchive;

final class MasterImageImportService
{
    public const CHUNK_SIZE = 5;
    public const MAX_FILES = 1000;
    public const MAX_FILE_BYTES = 25 * 1024 * 1024;
    public const MAX_TOTAL_BYTES = 2 * 1024 * 1024 * 1024;
    public const STATUSES = ['READY','IMPORTED','ALREADY_IMPORTED','PRODUCT_NOT_FOUND','EXISTING_MEDIA_REVIEW','MASTER_CHANGED_REVIEW','INVALID_MASTER_FILENAME','UNREADABLE_MASTER','DUPLICATE_MASTER_SKU','DUPLICATE_PRODUCT_SKU','IMPORT_STATE_MISMATCH','STAGING_CHANGED','STAGING_MISSING','PROCESSING_ERROR'];

    public function __construct(private DatabaseInterface $db, private ProductServiceInterface $products) {}

    public function getPaths(): array
    {
        $preferred = dirname(JPATH_ROOT) . '/fdshop-data/master-image-import';
        $fallback = JPATH_ROOT . '/tmp/fdshop-master-image-import';
        $base = $this->ensureBase($preferred) ? $preferred : $fallback;
        if (!$this->ensureBase($base)) {
            throw new RuntimeException('Der FDShop-Arbeitsbereich konnte nicht angelegt werden.');
        }
        foreach (['staging','state','batches','reports','tmp'] as $folder) {
            $path = $base . '/' . $folder;
            if (!is_dir($path) && !mkdir($path, 0750, true) && !is_dir($path)) {
                throw new RuntimeException('Arbeitsordner konnte nicht angelegt werden: ' . $folder);
            }
        }
        if (str_starts_with($base, JPATH_ROOT . '/')) {
            @file_put_contents($base . '/.htaccess', "Require all denied\nDeny from all\n");
            @file_put_contents($base . '/index.html', '');
        }
        return ['base'=>$base,'staging'=>$base.'/staging','state'=>$base.'/state/import-state.json','batches'=>$base.'/batches','reports'=>$base.'/reports','tmp'=>$base.'/tmp'];
    }

    private function ensureBase(string $path): bool
    {
        return (is_dir($path) || @mkdir($path, 0750, true)) && is_readable($path) && is_writable($path);
    }

    public static function normalizeSku(string $filename): ?string
    {
        return preg_match('/^fd([0-9]{4})\.png$/i', basename($filename), $m) === 1 ? 'FD'.$m[1] : null;
    }

    public function inventory(?string $sourceRoot = null): array
    {
        $root = $this->safeRoot($sourceRoot ?? $this->getPaths()['staging']);
        $rows=[]; $counts=[];
        foreach (new \DirectoryIterator($root) as $item) {
            if ($item->isDot() || $item->isDir()) continue;
            $name=$item->getFilename(); $sku=self::normalizeSku($name); $safe=$this->safeFile($root,$name);
            $sizeBefore=$safe ? @filesize($safe) : false; $mtimeBefore=$safe ? @filemtime($safe) : false;
            $info=$safe && is_readable($safe) && $sizeBefore !== false && $sizeBefore <= self::MAX_FILE_BYTES ? @getimagesize($safe) : false;
            $sha=$info && ($info['mime']??'')==='image/png' ? (string) @hash_file('sha256',$safe) : '';
            clearstatcache(true,$safe ?: null); $stable=$safe && $sizeBefore===@filesize($safe) && $mtimeBefore===@filemtime($safe);
            $valid=$sku!==null && $info!==false && ($info['mime']??'')==='image/png' && $sha!=='' && $stable;
            $rows[]=['filename'=>$name,'sku'=>$sku??'','path'=>$safe??'','size'=>(int)($sizeBefore?:0),'width'=>(int)($info[0]??0),'height'=>(int)($info[1]??0),'sha256'=>$sha,'valid'=>$valid,'stable'=>$stable,'note'=>$safe===null?'Unsicherer Pfad.':($sku===null?'Ungültiger Dateiname.':(!$stable?'Datei wurde während der Analyse verändert.':(!$valid?'Keine lesbare PNG-Datei.':'')))];
            if ($sku) $counts[$sku]=($counts[$sku]??0)+1;
        }
        usort($rows,fn($a,$b)=>[$a['sku'],$a['filename']]<=>[$b['sku'],$b['filename']]);
        foreach($rows as &$r) $r['duplicate']=$r['sku']!=='' && ($counts[$r['sku']]??0)>1; unset($r);
        return $rows;
    }

    public function analyse(?string $sourceRoot = null, ?string $statePath = null): array
    {
        $inventory=$this->inventory($sourceRoot); $state=$this->readState($statePath);
        $this->db->setQuery($this->db->getQuery(true)->select(['p.id','p.product_name','d.sku'])->from($this->db->quoteName('#__fdshop_products','p'))->innerJoin($this->db->quoteName('#__fdshop_products_details','d').' ON d.product_id=p.id')->where($this->db->quoteName('d.sku')." <> ''"));
        $products=[]; foreach($this->db->loadAssocList()?:[] as $p) $products[(string)$p['sku']][]=$p;
        $this->db->setQuery($this->db->getQuery(true)->select(['id','product_id','path_standard','path_small','path_mobile','path_invoice'])->from($this->db->quoteName('#__fdshop_media'))->where($this->db->quoteName('media_type').'='.$this->db->quote('image')));
        $media=[]; foreach($this->db->loadAssocList()?:[] as $m) $media[(int)$m['product_id']][]=$m;
        foreach($inventory as &$row) {
            $sku=$row['sku']; $row+=['product_id'=>0,'product_name'=>'','existing_media_count'=>0,'status'=>'READY','message'=>'Importierbar.'];
            if($sku==='') {$row['status']='INVALID_MASTER_FILENAME';$row['message']=$row['note'];continue;}
            if($row['duplicate']) {$row['status']='DUPLICATE_MASTER_SKU';$row['message']='Mehrere Masterdateien ergeben dieselbe SKU.';continue;}
            if(!$row['valid']) {$row['status']=$row['stable']?'UNREADABLE_MASTER':'STAGING_CHANGED';$row['message']=$row['note'];continue;}
            if(!isset($products[$sku])) {$row['status']='PRODUCT_NOT_FOUND';$row['message']='Kein Produkt mit dieser SKU gefunden.';continue;}
            if(count($products[$sku])!==1) {$row['status']='DUPLICATE_PRODUCT_SKU';$row['message']='Die Produkt-SKU ist nicht eindeutig.';continue;}
            $p=$products[$sku][0];$row['product_id']=(int)$p['id'];$row['product_name']=(string)$p['product_name'];$row['existing_media_count']=count($media[$row['product_id']]??[]);$entry=$state['imports'][$sku]??null;
            if(is_array($entry)) {
                if(($entry['master_sha256']??'')!==$row['sha256']) {$row['status']='MASTER_CHANGED_REVIEW';$row['message']='Seit dem Import liegt ein anderer Masterinhalt vor.';}
                elseif(!$this->targetMatches($entry,$sku,$row['product_id'])) {$row['status']='IMPORT_STATE_MISMATCH';$row['message']='Import-State und Media-Zielzustand stimmen nicht überein.';}
                else {$row['status']='ALREADY_IMPORTED';$row['message']='Dieser Master ist bereits vollständig importiert.';}
            } elseif($row['existing_media_count']>0) {$row['status']='EXISTING_MEDIA_REVIEW';$row['message']='Das Produkt besitzt bereits Media.';}
        } unset($row);
        $fingerprint=hash('sha256',implode("\n",array_map(fn($r)=>$r['filename']."\0".$r['size']."\0".$r['sha256'],$inventory)));
        return ['items'=>$inventory,'fingerprint'=>$fingerprint,'counts'=>array_count_values(array_column($inventory,'status')),'generated_at'=>gmdate('c')];
    }

    public function importOne(string $sku,string $expectedSha,int $userId,?string $sourceRoot=null,?string $statePath=null): array
    {
        $sku=strtoupper($sku); $statePath=$statePath??$this->getPaths()['state']; $lockPath=$statePath.'.lock'; $lock=fopen($lockPath,'c+');
        if(!$lock || !flock($lock,LOCK_EX)) throw new RuntimeException('Import-State konnte nicht gesperrt werden.');
        try {
            $analysis=$this->analyse($sourceRoot,$statePath); $row=null; foreach($analysis['items'] as $candidate) if($candidate['sku']===$sku){$row=$candidate;break;}
            if(!$row) return ['sku'=>$sku,'status'=>'STAGING_MISSING','message'=>'Masterdatei fehlt.'];
            if(!hash_equals($expectedSha,(string)$row['sha256'])) return ['sku'=>$sku,'status'=>'STAGING_CHANGED','message'=>'Masterdatei wurde seit dem Dry-Run verändert.'];
            if($row['status']!=='READY') return ['sku'=>$sku,'status'=>$row['status'],'message'=>$row['message']];
            $mediaId=0;
            try {
                $mediaId=$this->products->importProductImageFromLocalFile((int)$row['product_id'],(string)$row['path'],$userId);
                $this->db->setQuery($this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_media'))->where('id='.(int)$mediaId)); $media=$this->db->loadAssoc();
                if(!$media) throw new RuntimeException('Erzeugter Media-Datensatz fehlt.');
                foreach(['path_standard','path_small','path_mobile','path_invoice'] as $field) if(empty($media[$field])||!is_file(JPATH_ROOT.$media[$field])) throw new RuntimeException('Media-Derivat fehlt: '.$field);
                $state=$this->readState($statePath);$state['imports'][$sku]=['sku'=>$sku,'product_id'=>(int)$row['product_id'],'master_filename'=>$row['filename'],'master_sha256'=>$row['sha256'],'media_id'=>$mediaId,'path_standard'=>$media['path_standard'],'path_small'=>$media['path_small'],'path_mobile'=>$media['path_mobile'],'path_invoice'=>$media['path_invoice'],'tool_version'=>'2.0'];$this->writeJsonAtomic($statePath,$state);
                return ['sku'=>$sku,'status'=>'IMPORTED','message'=>'Produktbild wurde importiert.','media_id'=>$mediaId];
            } catch(Throwable $e) { if($mediaId>0) try{$this->products->deleteProductImage((int)$row['product_id'],$mediaId);}catch(Throwable){} return ['sku'=>$sku,'status'=>'PROCESSING_ERROR','message'=>$e->getMessage()]; }
        } finally {flock($lock,LOCK_UN);fclose($lock);}
    }

    public function createBatch(array $skus,int $userId): array
    {
        $analysis=$this->analyse();$available=[];foreach($analysis['items'] as $row)if($row['status']==='READY')$available[$row['sku']]=$row;
        $items=[];foreach(array_values(array_unique(array_map('strtoupper',$skus))) as $sku)if(isset($available[$sku]))$items[]=['sku'=>$sku,'sha256'=>$available[$sku]['sha256'],'status'=>'PENDING'];
        if(!$items) throw new RuntimeException('Keine importierbaren READY-Einträge ausgewählt.');
        $id=bin2hex(random_bytes(16));$batch=['id'=>$id,'user_id'=>$userId,'created_at'=>time(),'complete'=>false,'items'=>$items,'results'=>[]];$this->writeJsonAtomic($this->batchPath($id),$batch);return $batch;
    }

    public function processBatch(string $id,int $userId): array
    {
        if(!preg_match('/^[a-f0-9]{32}$/',$id))throw new RuntimeException('Ungültige Batch-ID.');$path=$this->batchPath($id);$lock=fopen($path.'.lock','c+');
        if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))return ['busy'=>true,'complete'=>false];
        try{$batch=$this->readJson($path);if((int)($batch['user_id']??0)!==$userId)throw new RuntimeException('Batch gehört nicht zum aktuellen Benutzer.');if(time()-(int)$batch['created_at']>86400)throw new RuntimeException('Batch ist abgelaufen.');$processed=0;
            foreach($batch['items'] as &$item){if($item['status']!=='PENDING'||$processed>=self::CHUNK_SIZE)continue;$result=$this->importOne($item['sku'],$item['sha256'],$userId);$item['status']=$result['status'];$batch['results'][]=$result;$processed++;}unset($item);
            $remaining=count(array_filter($batch['items'],fn($i)=>$i['status']==='PENDING'));$batch['complete']=$remaining===0;$this->writeJsonAtomic($path,$batch);return ['busy'=>false,'complete'=>$batch['complete'],'processed'=>$processed,'remaining'=>$remaining,'total'=>count($batch['items']),'results'=>$processed > 0 ? array_slice($batch['results'],-$processed) : [],'counts'=>array_count_values(array_column($batch['items'],'status'))];
        }finally{flock($lock,LOCK_UN);fclose($lock);}
    }

    public function stageUploads(array $files): array
    {
        $result=[];$normalized=$this->normalizeUploads($files);if(count($normalized)>self::MAX_FILES)throw new RuntimeException('Zu viele Upload-Dateien.');
        foreach($normalized as $file){$name=(string)($file['name']??'');if((int)($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK){$result[]=['filename'=>$name,'status'=>'UPLOAD_ERROR','message'=>'Upload wurde nicht vollständig empfangen.'];continue;}$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
            if($ext==='zip'){$result=array_merge($result,$this->stageZip((string)$file['tmp_name'],$name));continue;}$result[]=$this->stagePng((string)$file['tmp_name'],$name,true);}
        return $result;
    }

    private function stageZip(string $tmp,string $name): array
    {
        if(!class_exists(ZipArchive::class))return [['filename'=>$name,'status'=>'ZIP_UNAVAILABLE','message'=>'ZIP-Unterstützung ist auf diesem Server nicht verfügbar.']];$zip=new ZipArchive();if($zip->open($tmp)!==true)return [['filename'=>$name,'status'=>'UPLOAD_ERROR','message'=>'ZIP-Datei ist beschädigt.']];
        if($zip->numFiles>self::MAX_FILES){$zip->close();throw new RuntimeException('ZIP enthält zu viele Dateien.');}$entries=[];$seen=[];$total=0;
        for($i=0;$i<$zip->numFiles;$i++){$stat=$zip->statIndex($i);$entry=(string)$stat['name'];$unix=str_replace('\\','/',$entry);if(str_starts_with($unix,'/')||preg_match('#(^|/)\.\.(/|$)#',$unix)||preg_match('/^[A-Za-z]:/',$unix)||str_contains($unix,'/')||str_ends_with($unix,'/')){$zip->close();throw new RuntimeException('ZIP enthält einen unzulässigen Pfad.');}$sku=self::normalizeSku($entry);if(!$sku)continue;if(isset($seen[$sku])){$zip->close();throw new RuntimeException('ZIP enthält doppelte Master-SKU.');}$seen[$sku]=true;$size=(int)$stat['size'];$total+=$size;if($size>self::MAX_FILE_BYTES||$total>self::MAX_TOTAL_BYTES){$zip->close();throw new RuntimeException('ZIP überschreitet die Sicherheitsgrenzen.');}$entries[]=[$i,$entry];}
        $result=[];$tmpDir=$this->getPaths()['tmp'].'/'.bin2hex(random_bytes(8));mkdir($tmpDir,0750,true);try{foreach($entries as [$index,$entry]){$stream=$zip->getStream($entry);$target=$tmpDir.'/'.basename($entry);$out=fopen($target,'wb');stream_copy_to_stream($stream,$out);fclose($stream);fclose($out);$result[]=$this->stagePng($target,basename($entry),false);}}finally{$zip->close();foreach(glob($tmpDir.'/*')?:[] as $f)@unlink($f);@rmdir($tmpDir);}return $result;
    }

    private function stagePng(string $tmp,string $name,bool $uploaded): array
    {
        if(self::normalizeSku($name)===null)return ['filename'=>$name,'status'=>'INVALID_MASTER_FILENAME','message'=>'Dateiname entspricht nicht fdXXXX.png.'];if($uploaded&&!is_uploaded_file($tmp))return ['filename'=>$name,'status'=>'UPLOAD_ERROR','message'=>'Ungültige Uploadquelle.'];$imageInfo=is_file($tmp)?@getimagesize($tmp):false;if(!is_file($tmp)||filesize($tmp)>self::MAX_FILE_BYTES||$imageInfo===false||($imageInfo['mime']??'')!=='image/png')return ['filename'=>$name,'status'=>'UNREADABLE_MASTER','message'=>'Keine gültige PNG-Datei oder Größenlimit überschritten.'];
        $dest=$this->getPaths()['staging'].'/'.basename($name);$sha=hash_file('sha256',$tmp);if(is_file($dest))return ['filename'=>$name,'status'=>hash_equals((string)hash_file('sha256',$dest),$sha)?'ALREADY_STAGED':'STAGING_CONFLICT','message'=>'Datei/SKU ist bereits im Staging vorhanden.'];$part=$dest.'.part.'.bin2hex(random_bytes(4));if(!copy($tmp,$part)||!rename($part,$dest)){@unlink($part);throw new RuntimeException('Master konnte nicht atomar ins Staging übernommen werden.');}chmod($dest,0640);return ['filename'=>$name,'status'=>'STAGED','message'=>'Master wurde bereitgestellt.'];
    }

    public function clearStaging(): int
    {
        foreach(glob($this->getPaths()['batches'].'/*.json')?:[] as $batch){$data=$this->readJson($batch);if(empty($data['complete'])&&time()-(int)($data['created_at']??0)<86400)throw new RuntimeException('Während eines aktiven Batches kann Staging nicht geleert werden.');}
        $count=0;foreach(new \DirectoryIterator($this->getPaths()['staging']) as $item){if($item->isDot())continue;$safe=$this->safeFile($this->getPaths()['staging'],$item->getFilename(),true);if($safe&&(@unlink($safe)||!file_exists($safe)))$count++;}return $count;
    }

    private function targetMatches(array $entry,string $sku,int $productId): bool
    {
        if(($entry['sku']??'')!==$sku||(int)($entry['product_id']??0)!==$productId)return false;$this->db->setQuery($this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_media'))->where('id='.(int)($entry['media_id']??0))->where('product_id='.$productId));$media=$this->db->loadAssoc();if(!$media)return false;foreach(['path_standard','path_small','path_mobile','path_invoice'] as $field)if(($media[$field]??'')!==($entry[$field]??'')||!is_file(JPATH_ROOT.$media[$field]))return false;return true;
    }
    private function readState(?string $path=null): array { $p=$path??$this->getPaths()['state'];return is_file($p)?$this->readJson($p):['version'=>1,'imports'=>[]]; }
    private function readJson(string $path): array { $d=json_decode((string)@file_get_contents($path),true);if(!is_array($d))throw new RuntimeException('FDShop-State ist beschädigt oder unlesbar.');return $d; }
    private function writeJsonAtomic(string $path,array $data): void { $tmp=$path.'.tmp.'.bin2hex(random_bytes(4));if(file_put_contents($tmp,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX)===false||!rename($tmp,$path)){@unlink($tmp);throw new RuntimeException('State konnte nicht atomar gespeichert werden.');} }
    private function batchPath(string $id): string{return $this->getPaths()['batches'].'/'.$id.'.json';}
    private function safeRoot(string $root): string{$real=realpath($root);if(!$real||!is_dir($real))throw new RuntimeException('Source-Root ist nicht verfügbar.');return $real;}
    private function safeFile(string $root,string $name,bool $allowLink=false): ?string{$base=realpath($root);$path=$base!==false?realpath($base.'/'.basename($name)):false;if(!$base||!$path||(!$allowLink&&is_link($base.'/'.basename($name)))||!is_file($path)||!str_starts_with($path,rtrim($base,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR))return null;return $path;}
    private function normalizeUploads(array $files): array {if(!isset($files['name']))return[];if(!is_array($files['name']))return[$files];$out=[];foreach($files['name'] as $i=>$name)$out[]=['name'=>$name,'tmp_name'=>$files['tmp_name'][$i]??'','error'=>$files['error'][$i]??UPLOAD_ERR_NO_FILE,'size'=>$files['size'][$i]??0];return$out;}
}
