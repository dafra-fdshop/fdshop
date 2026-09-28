<?php
namespace FDShop\Component\FDShop\Administrator\Service;
defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class WatchlistService implements WatchlistServiceInterface
{
    public function __construct(private readonly DatabaseInterface $db) {}

    public function activate(int $userId, int $productId): void
    {
        if ($userId < 1) throw new \DomainException('Bitte melden Sie sich an, um eine Verfügbarkeitsbenachrichtigung zu aktivieren.');
        $product=$this->product($productId);
        if ((string)$product->in_stock !== 'Ausverkauft') throw new \DomainException('Dieses Produkt ist bereits wieder verfügbar.');
        $now=Factory::getDate()->toSql();
        $sql='INSERT INTO '.$this->db->quoteName('#__fdshop_product_watchlist').' ('.implode(',',$this->db->quoteName(['product_id','user_id','status','created','modified'])).') VALUES ('.(int)$productId.','.(int)$userId.','.$this->db->quote('active').','.$this->db->quote($now).','.$this->db->quote($now).') ON DUPLICATE KEY UPDATE status=VALUES(status),created=VALUES(created),modified=VALUES(modified),notified_at=NULL';
        $this->db->setQuery($sql)->execute();
    }

    public function remove(int $userId, int $watchId): void
    {
        if($userId<1||$watchId<1)return;
        $q=$this->db->getQuery(true)->delete($this->db->quoteName('#__fdshop_product_watchlist'))->where('id='.(int)$watchId)->where('user_id='.(int)$userId)->where('status='.$this->db->quote('active'));
        $this->db->setQuery($q)->execute();
    }

    public function forUser(int $userId): array
    {
        if($userId<1)return [];
        $q=$this->db->getQuery(true)->select(['w.id','w.product_id','w.created','p.product_name','MIN(m.category_id) AS category_id'])->from($this->db->quoteName('#__fdshop_product_watchlist','w'))->innerJoin($this->db->quoteName('#__fdshop_products','p').' ON p.id=w.product_id')->leftJoin($this->db->quoteName('#__fdshop_product_category_map','m').' ON m.product_id=p.id')->where('w.user_id='.(int)$userId)->where('w.status='.$this->db->quote('active'))->group('w.id,w.product_id,w.created,p.product_name')->order('w.created DESC');
        $this->db->setQuery($q);return (array)$this->db->loadObjectList();
    }

    public function forProduct(int $productId): array
    {
        if($productId<1)return [];
        $q=$this->db->getQuery(true)->select(['w.id','w.user_id','w.created','u.name','u.email'])->from($this->db->quoteName('#__fdshop_product_watchlist','w'))->innerJoin($this->db->quoteName('#__users','u').' ON u.id=w.user_id')->where('w.product_id='.(int)$productId)->where('w.status='.$this->db->quote('active'))->order('w.created ASC');
        $this->db->setQuery($q);return (array)$this->db->loadObjectList();
    }

    public function dashboard(): array
    {
        $q=$this->db->getQuery(true)->select(['p.id AS product_id','p.product_name','COUNT(*) AS waiting_count'])->from($this->db->quoteName('#__fdshop_product_watchlist','w'))->innerJoin($this->db->quoteName('#__fdshop_products','p').' ON p.id=w.product_id')->where('w.status='.$this->db->quote('active'))->group('p.id,p.product_name')->order('waiting_count DESC,p.product_name ASC');
        $this->db->setQuery($q);return (array)$this->db->loadObjectList();
    }

    public function notifyAvailable(int $productId): array
    {
        $product=$this->product($productId);
        if((string)$product->in_stock==='Ausverkauft')throw new \DomainException('Das Produkt ist noch ausverkauft. Es wurden keine E-Mails versendet.');
        $success=0;$errors=[];
        foreach($this->forProduct($productId) as $watch){
            try{
                if(!filter_var((string)$watch->email,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('Ungültige Kundenadresse.');
                $url=RouteHelper::getProductCanonicalRoute($productId);$mailer=Factory::getMailer();$mailer->addRecipient((string)$watch->email,(string)$watch->name);$mailer->setSubject((string)$product->product_name.' ist wieder verfügbar');$mailer->isHtml(true);$mailer->setBody('<p>Das von Ihnen vorgemerkte Produkt <strong>'.htmlspecialchars((string)$product->product_name,ENT_QUOTES,'UTF-8').'</strong> ist wieder verfügbar.</p><p><a href="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'">Produkt ansehen</a></p>');if($mailer->send()!==true)throw new \RuntimeException('Mailer hat die Nachricht nicht bestätigt.');
                $now=Factory::getDate()->toSql();$q=$this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_product_watchlist'))->set('status='.$this->db->quote('notified'))->set('notified_at='.$this->db->quote($now))->set('modified='.$this->db->quote($now))->where('id='.(int)$watch->id)->where('status='.$this->db->quote('active'));$this->db->setQuery($q)->execute();$success++;
            }catch(\Throwable $e){$errors[]=['watch_id'=>(int)$watch->id,'message'=>$e->getMessage()];}
        }
        return ['success'=>$success,'failed'=>count($errors),'errors'=>$errors];
    }

    private function product(int $productId): object
    {
        $q=$this->db->getQuery(true)->select(['id','product_name','in_stock'])->from($this->db->quoteName('#__fdshop_products'))->where('id='.(int)$productId)->where('is_active=1')->where('is_deleted=0');$this->db->setQuery($q);$product=$this->db->loadObject();if(!$product)throw new \DomainException('Das Produkt wurde nicht gefunden.');return $product;
    }
}
