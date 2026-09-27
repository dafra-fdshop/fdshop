<?php
namespace FDShop\Component\FDShop\Administrator\Service;
defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

final class BuyerEligibilityService implements BuyerEligibilityServiceInterface
{
    public const STANDARD = 'standard';
    public const PERMIT_HOLDER = 'permit_holder';
    private array $userCache = [];
    private array $productCache = [];
    private array $groupCache = [];

    public function __construct(private readonly DatabaseInterface $db) {}

    public function userStatus(int $userId): string
    {
        if ($userId < 1) return self::STANDARD;
        if (isset($this->userCache[$userId])) return $this->userCache[$userId];
        $q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__fdshop_user_buyer_group_map','m'))
            ->innerJoin($this->db->quoteName('#__fdshop_buyer_groups','g').' ON g.id=m.buyer_group_id')
            ->where('m.user_id='.(int)$userId)->where('g.alias='.$this->db->quote(self::PERMIT_HOLDER))->where('g.is_active=1');
        $this->db->setQuery($q);
        return $this->userCache[$userId]=(int)$this->db->loadResult()>0?self::PERMIT_HOLDER:self::STANDARD;
    }
    public function userHasF3Permission(int $userId): bool { return $this->userStatus($userId)===self::PERMIT_HOLDER; }
    public function requiredBuyerStatus(int $productId): string
    {
        if (isset($this->productCache[$productId])) return $this->productCache[$productId];
        $q=$this->db->getQuery(true)->select('g.alias')->from($this->db->quoteName('#__fdshop_products','p'))
            ->leftJoin($this->db->quoteName('#__fdshop_buyer_groups','g').' ON g.id=p.buyer_group_id')->where('p.id='.(int)$productId);
        $this->db->setQuery($q);$alias=(string)$this->db->loadResult();
        return $this->productCache[$productId]=$alias===self::PERMIT_HOLDER?self::PERMIT_HOLDER:self::STANDARD;
    }
    public function canPurchaseProduct(int $userId,int $productId):bool{return $this->requiredBuyerStatus($productId)!==self::PERMIT_HOLDER||$this->userHasF3Permission($userId);}
    public function assertProductsEligible(int $userId,array $productIds,string $context='cart'):void
    {
        $ids=array_values(array_unique(array_filter(array_map('intval',$productIds))));if($ids===[])return;
        $q=$this->db->getQuery(true)->select(['p.id','p.product_name','g.alias'])->from($this->db->quoteName('#__fdshop_products','p'))
            ->leftJoin($this->db->quoteName('#__fdshop_buyer_groups','g').' ON g.id=p.buyer_group_id')->whereIn('p.id',$ids);
        $this->db->setQuery($q);$blocked=[];
        foreach((array)$this->db->loadObjectList() as $row){$this->productCache[(int)$row->id]=(string)$row->alias===self::PERMIT_HOLDER?self::PERMIT_HOLDER:self::STANDARD;if($this->productCache[(int)$row->id]===self::PERMIT_HOLDER&&!$this->userHasF3Permission($userId))$blocked[]=(string)$row->product_name;}
        if($blocked!==[]){$names=implode(', ',$blocked);if($context==='admin')throw new \DomainException('Der Kunde ist für das Produkt „'.$names.'“ nicht berechtigt.');if($context==='checkout')throw new \DomainException('Sie sind für das Produkt „'.$names.'“ nicht berechtigt. Bitte entfernen Sie es aus dem Warenkorb oder melden Sie sich bei uns.');throw new \DomainException('Sie sind für das Produkt „'.$names.'“ nicht berechtigt.');}
    }
    public function setUserStatus(int $userId,string $status):void
    {
        if($userId<1||!in_array($status,[self::STANDARD,self::PERMIT_HOLDER],true))throw new \InvalidArgumentException('Ungültige FDShop-Käuferberechtigung.');
        $q=$this->db->getQuery(true)->delete($this->db->quoteName('#__fdshop_user_buyer_group_map'))->where('user_id='.(int)$userId);$this->db->setQuery($q)->execute();
        $q=$this->db->getQuery(true)->insert($this->db->quoteName('#__fdshop_user_buyer_group_map'))->columns($this->db->quoteName(['user_id','buyer_group_id']))->values((int)$userId.','.$this->groupId($status));$this->db->setQuery($q)->execute();$this->userCache[$userId]=$status;
    }
    public function groupId(string $status):int
    {
        if(isset($this->groupCache[$status]))return $this->groupCache[$status];if(!in_array($status,[self::STANDARD,self::PERMIT_HOLDER],true))throw new \InvalidArgumentException('Unbekannte Käufergruppe.');
        $q=$this->db->getQuery(true)->select('id')->from($this->db->quoteName('#__fdshop_buyer_groups'))->where('alias='.$this->db->quote($status))->where('is_active=1');$this->db->setQuery($q);$id=(int)$this->db->loadResult();if($id<1)throw new \RuntimeException('FDShop-Käufergruppe '.$status.' fehlt.');return $this->groupCache[$status]=$id;
    }
}
