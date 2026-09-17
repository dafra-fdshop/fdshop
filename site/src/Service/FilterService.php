<?php
namespace FDShop\Component\FDShop\Site\Service;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\DatabaseQuery;

final class FilterService
{
    private const KEYS=['manufacturer','availability','duration','caliber','nem','firing_type','product_type'];
    private const RANGE_KEYS=['duration','caliber','nem'];
    private const OPTION_KEYS=['firing_type','product_type'];
    public function __construct(private readonly DatabaseInterface $db){}

    public function getDefinitions(int $categoryId=0,bool $activeOnly=true):array
    {
        $q=$this->db->getQuery(true)->select(['f.id','f.filter_key','f.label','f.is_active','f.ordering'])->from($this->db->quoteName('#__fdshop_filters','f'))
            ->where('(NOT EXISTS (SELECT 1 FROM '.$this->db->quoteName('#__fdshop_filter_category_map','fcm').' WHERE fcm.filter_id=f.id) OR EXISTS (SELECT 1 FROM '.$this->db->quoteName('#__fdshop_filter_category_map','fcm2').' WHERE fcm2.filter_id=f.id AND fcm2.category_id='.(int)$categoryId.'))')->order('f.ordering ASC,f.id ASC');
        if($activeOnly)$q->where('f.is_active=1');$this->db->setQuery($q);$defs=[];
        foreach($this->db->loadObjectList()?:[] as$f){if(!in_array($f->filter_key,self::KEYS,true))continue;$f->ranges=[];$f->options=[];$defs[$f->filter_key]=$f;}
        if(!$defs)return[];
        $ids=array_map(static fn($f)=>(int)$f->id,$defs);$byId=[];foreach($defs as$key=>$f)$byId[(int)$f->id]=$key;
        $q=$this->db->getQuery(true)->select(['r.id','r.filter_id','r.label','r.value_from','r.value_to','r.is_active','r.ordering'])->from($this->db->quoteName('#__fdshop_filter_ranges','r'))->whereIn('r.filter_id',$ids)
            ->where('(NOT EXISTS (SELECT 1 FROM '.$this->db->quoteName('#__fdshop_filter_range_category_map','rcm').' WHERE rcm.range_id=r.id) OR EXISTS (SELECT 1 FROM '.$this->db->quoteName('#__fdshop_filter_range_category_map','rcm2').' WHERE rcm2.range_id=r.id AND rcm2.category_id='.(int)$categoryId.'))')->order('r.ordering ASC,r.id ASC');
        if($activeOnly)$q->where('r.is_active=1');$this->db->setQuery($q);foreach($this->db->loadObjectList()?:[] as$r)if(isset($byId[(int)$r->filter_id]))$defs[$byId[(int)$r->filter_id]]->ranges[]=$r;
        $q=$this->db->getQuery(true)->select(['o.id','o.filter_id','o.option_key','o.label','o.is_active','o.ordering'])->from($this->db->quoteName('#__fdshop_filter_options','o'))->whereIn('o.filter_id',$ids)
            ->where('(NOT EXISTS (SELECT 1 FROM '.$this->db->quoteName('#__fdshop_filter_option_category_map','ocm').' WHERE ocm.option_id=o.id) OR EXISTS (SELECT 1 FROM '.$this->db->quoteName('#__fdshop_filter_option_category_map','ocm2').' WHERE ocm2.option_id=o.id AND ocm2.category_id='.(int)$categoryId.'))')->order('o.ordering ASC,o.id ASC');
        if($activeOnly)$q->where('o.is_active=1');$this->db->setQuery($q);foreach($this->db->loadObjectList()?:[] as$o)if(isset($byId[(int)$o->filter_id]))$defs[$byId[(int)$o->filter_id]]->options[]=$o;
        return$defs;
    }

    public function normaliseState(array $raw,?array $defs=null):array
    {
        $defs??=$this->getDefinitions();$state=[];
        foreach(self::KEYS as$key){if(!isset($defs[$key]))continue;$v=$raw[$key]??[];$v=is_array($v)?$v:[$v];$v=array_values(array_unique(array_filter(array_map('strval',$v),fn($x)=>$x!=='')));
            if($key==='manufacturer'){$v=array_values(array_filter(array_map('intval',$v),fn($x)=>$x>0));}
            elseif($key==='availability')$v=array_values(array_intersect($v,['available','unavailable']));
            elseif(in_array($key,self::RANGE_KEYS,true))$v=array_values(array_intersect(array_map('intval',$v),array_map(fn($r)=>(int)$r->id,$defs[$key]->ranges)));
            else $v=array_values(array_intersect(array_map('intval',$v),array_map(fn($o)=>(int)$o->id,$defs[$key]->options)));
            if($v)$state[$key]=$v;
        }return$state;
    }

    public function apply(DatabaseQuery $q,array $state,array $defs):void
    {
        if(!empty($state['manufacturer']))$q->whereIn('p.manufacturer_id',array_map('intval',$state['manufacturer']));
        if(!empty($state['availability'])){$q->leftJoin($this->db->quoteName('#__fdshop_products_details','pfd').' ON pfd.product_id=p.id');if(count($state['availability'])===1)$q->where('COALESCE(pfd.is_in_stock,0)='.($state['availability'][0]==='available'?1:0));}
        $express=['duration'=>"CAST(REPLACE(p.burn_time, ',', '.') AS DECIMAL(12,3))",'caliber'=>"CAST(REPLACE(p.caliber, ',', '.') AS DECIMAL(12,3))",'nem'=>'p.nem'];$valid=['duration'=>"TRIM(p.burn_time) REGEXP '^[0-9]+([.,][0-9]+)?'",'caliber'=>"TRIM(p.caliber) REGEXP '^[0-9]+([.,][0-9]+)?'",'nem'=>'p.nem>0'];
        foreach(self::RANGE_KEYS as$key){if(empty($state[$key])||empty($defs[$key]))continue;$selected=array_flip(array_map('intval',$state[$key]));$parts=[];foreach($defs[$key]->ranges as$r){if(!isset($selected[(int)$r->id]))continue;$b=[$valid[$key]];if($r->value_from!==null)$b[]=$express[$key].'>='.(float)$r->value_from;if($r->value_to!==null)$b[]=$express[$key].'<'.(float)$r->value_to;$parts[]='('.implode(' AND ',$b).')';}if($parts)$q->where('('.implode(' OR ',$parts).')');}
        foreach(self::OPTION_KEYS as$key){if(empty($state[$key]))continue;$ids=array_map('intval',$state[$key]);$filterId=(int)$defs[$key]->id;$q->where('EXISTS (SELECT 1 FROM '.$this->db->quoteName('#__fdshop_product_filter_option_map','pfom_'.$key).' INNER JOIN '.$this->db->quoteName('#__fdshop_filter_options','fo_'.$key).' ON fo_'.$key.'.id=pfom_'.$key.'.option_id WHERE pfom_'.$key.'.product_id=p.id AND fo_'.$key.'.filter_id='.$filterId.' AND pfom_'.$key.'.option_id IN ('.implode(',',$ids).'))');}
    }

    public function getFacets(int $categoryId,array $state,array $defs):array{$out=[];foreach($defs as$key=>$def){$options=$key==='manufacturer'?$this->manufacturerOptions($categoryId,$state,$defs):($key==='availability'?$this->availabilityOptions($categoryId,$state,$defs):(in_array($key,self::RANGE_KEYS,true)?$this->rangeOptions($key,$categoryId,$state,$defs):$this->discreteOptions($key,$categoryId,$state,$defs)));$out[$key]=['definition'=>$def,'options'=>$options];}return$out;}
    public function chips(array $state,array $facets):array{$out=[];foreach($state as$key=>$values)foreach($facets[$key]['options']??[]as$o)if(in_array((string)$o['value'],array_map('strval',$values),true))$out[]=['key'=>$key,'value'=>(string)$o['value'],'label'=>(string)$o['label']];return$out;}
    private function base(int $cat,array $state,array $defs,string $excluded):DatabaseQuery{unset($state[$excluded]);$q=$this->db->getQuery(true)->from($this->db->quoteName('#__fdshop_products','p'))->innerJoin($this->db->quoteName('#__fdshop_product_category_map','pcm').' ON pcm.product_id=p.id')->where('pcm.category_id='.(int)$cat)->where('p.is_active=1')->where('p.is_deleted=0')->where($this->publishedNow('p'));$this->apply($q,$state,$defs);return$q;}
    private function manufacturerOptions(int$c,array$s,array$d):array
    {
        $counts=$this->base($c,$s,$d,'manufacturer')->select(['p.manufacturer_id','COUNT(DISTINCT p.id) hits'])->group('p.manufacturer_id');
        $q=$this->db->getQuery(true)->select(['m.id value','m.manufacturer_name label','COALESCE(fc.hits,0) hits'])
            ->from($this->db->quoteName('#__fdshop_manufacturers','m'))
            ->innerJoin($this->db->quoteName('#__fdshop_products','bp').' ON bp.manufacturer_id=m.id AND bp.is_active=1 AND bp.is_deleted=0 AND '.$this->publishedNow('bp'))
            ->innerJoin($this->db->quoteName('#__fdshop_product_category_map','bpcm').' ON bpcm.product_id=bp.id AND bpcm.category_id='.(int)$c)
            ->leftJoin('('.(string)$counts.') fc ON fc.manufacturer_id=m.id')
            ->where('m.is_active=1')->group(['m.id','m.manufacturer_name','fc.hits'])->order('m.manufacturer_name');
        $this->db->setQuery($q);return array_map(fn($r)=>['value'=>(int)$r->value,'label'=>$r->label,'count'=>(int)$r->hits],$this->db->loadObjectList()?:[]);
    }
    private function publishedNow(string $alias):string{$now=$this->db->quote(Factory::getDate()->toSql());return '(('.$alias.'.publish_up IS NULL OR '.$alias.'.publish_up<='.$now.') AND ('.$alias.'.publish_down IS NULL OR '.$alias.'.publish_down>='.$now.'))';}
    private function availabilityOptions(int$c,array$s,array$d):array{$q=$this->base($c,$s,$d,'availability')->select(['COALESCE(pd.is_in_stock,0) available','COUNT(DISTINCT p.id) hits'])->leftJoin($this->db->quoteName('#__fdshop_products_details','pd').' ON pd.product_id=p.id')->group('COALESCE(pd.is_in_stock,0)');$this->db->setQuery($q);$x=[0=>0,1=>0];foreach($this->db->loadObjectList()?:[]as$r)$x[(int)$r->available]=(int)$r->hits;return[['value'=>'available','label'=>'Auf Lager','count'=>$x[1]],['value'=>'unavailable','label'=>'Nicht auf Lager','count'=>$x[0]]];}
    private function rangeOptions(string$key,int$c,array$s,array$d):array{$e=['duration'=>"CAST(REPLACE(p.burn_time, ',', '.') AS DECIMAL(12,3))",'caliber'=>"CAST(REPLACE(p.caliber, ',', '.') AS DECIMAL(12,3))",'nem'=>'p.nem'][$key];$v=['duration'=>"TRIM(p.burn_time) REGEXP '^[0-9]+([.,][0-9]+)?'",'caliber'=>"TRIM(p.caliber) REGEXP '^[0-9]+([.,][0-9]+)?'",'nem'=>'p.nem>0'][$key];$q=$this->base($c,$s,$d,$key);$aliases=[];foreach($d[$key]->ranges as$r){$b=[$v];if($r->value_from!==null)$b[]=$e.'>='.(float)$r->value_from;if($r->value_to!==null)$b[]=$e.'<'.(float)$r->value_to;$a='r_'.(int)$r->id;$aliases[(int)$r->id]=$a;$q->select('COUNT(DISTINCT CASE WHEN '.implode(' AND ',$b).' THEN p.id END) '.$this->db->quoteName($a));}if(!$aliases)return[];$this->db->setQuery($q);$counts=$this->db->loadAssoc()?:[];$out=[];foreach($d[$key]->ranges as$r)$out[]=['value'=>(int)$r->id,'label'=>(string)$r->label,'count'=>(int)($counts[$aliases[(int)$r->id]]??0)];return$out;}
    private function discreteOptions(string$key,int$c,array$s,array$d):array
    {
        $q=$this->base($c,$s,$d,$key);$aliases=[];
        foreach($d[$key]->options as$o){$alias='o_'.(int)$o->id;$aliases[(int)$o->id]=$alias;$q->select('COUNT(DISTINCT CASE WHEN EXISTS (SELECT 1 FROM '.$this->db->quoteName('#__fdshop_product_filter_option_map','x_'.$o->id).' WHERE x_'.$o->id.'.product_id=p.id AND x_'.$o->id.'.option_id='.(int)$o->id.') THEN p.id END) '.$this->db->quoteName($alias));}
        if(!$aliases)return[];$this->db->setQuery($q);$counts=$this->db->loadAssoc()?:[];$out=[];
        foreach($d[$key]->options as$o)$out[]=['value'=>(int)$o->id,'label'=>(string)$o->label,'count'=>(int)($counts[$aliases[(int)$o->id]]??0)];
        return$out;
    }
}
