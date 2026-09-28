<?php
namespace FDShop\Component\FDShop\Site\Service;
defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class ProductQuestionService implements ProductQuestionServiceInterface
{
    public function __construct(private readonly DatabaseInterface $db) {}

    public function send(int $userId,int $productId,array $data):void
    {
        if(trim((string)($data['website']??''))!=='')throw new \DomainException('Die Anfrage konnte nicht verarbeitet werden.');
        $this->rateLimit($userId);
        $name=trim(strip_tags((string)($data['name']??'')));$email=trim((string)($data['email']??''));$question=trim(strip_tags((string)($data['question']??'')));
        if($name===''||mb_strlen($name)>120||preg_match('/[\r\n]/',$name))throw new \DomainException('Bitte geben Sie einen gültigen Namen ein.');
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$email))throw new \DomainException('Bitte geben Sie eine gültige E-Mail-Adresse ein.');
        if(mb_strlen($question)<10||mb_strlen($question)>3000)throw new \DomainException('Ihre Frage muss zwischen 10 und 3000 Zeichen lang sein.');
        $q=$this->db->getQuery(true)->select(['p.id','p.product_name','d.sku','MIN(m.category_id) AS category_id'])->from($this->db->quoteName('#__fdshop_products','p'))->leftJoin($this->db->quoteName('#__fdshop_products_details','d').' ON d.product_id=p.id')->leftJoin($this->db->quoteName('#__fdshop_product_category_map','m').' ON m.product_id=p.id')->where('p.id='.(int)$productId)->where('p.is_active=1')->where('p.is_deleted=0')->group('p.id,p.product_name,d.sku');$this->db->setQuery($q);$product=$this->db->loadObject();if(!$product)throw new \DomainException('Das Produkt wurde nicht gefunden.');
        $url=RouteHelper::getProductCanonicalRoute((int)$product->id,(int)$product->category_id);$body='<p><strong>Produkt:</strong> '.htmlspecialchars((string)$product->product_name,ENT_QUOTES,'UTF-8').'<br><strong>SKU:</strong> '.htmlspecialchars((string)$product->sku,ENT_QUOTES,'UTF-8').'<br><strong>Produkt-ID:</strong> '.(int)$product->id.'<br><strong>Produkt-URL:</strong> <a href="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'">'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'</a></p><p><strong>Name:</strong> '.htmlspecialchars($name,ENT_QUOTES,'UTF-8').'<br><strong>E-Mail:</strong> '.htmlspecialchars($email,ENT_QUOTES,'UTF-8').($userId>0?'<br><strong>User-ID:</strong> '.$userId:'').'</p><p><strong>Frage:</strong><br>'.nl2br(htmlspecialchars($question,ENT_QUOTES,'UTF-8')).'</p>';
        $mailer=Factory::getMailer();$mailer->addRecipient($this->companyEmail());$mailer->addReplyTo($email,$name);$mailer->setSubject('Produktfrage: '.str_replace(["\r","\n"],' ',(string)$product->product_name));$mailer->isHtml(true);$mailer->setBody($body);if($mailer->send()!==true)throw new \RuntimeException('Die Produktfrage konnte nicht versendet werden.');
    }

    private function rateLimit(int $userId):void
    {
        $app=Factory::getApplication();$key=$userId>0?'user-'.$userId:'ip-'.hash('sha256',(string)$app->input->server->getString('REMOTE_ADDR','unknown'));$session=$app->getSession();$all=(array)$session->get('com_fdshop.product_question.rate',[]);$now=time();$recent=array_values(array_filter((array)($all[$key]??[]),static fn($time)=>(int)$time>$now-900));if(count($recent)>=3)throw new \DomainException('Bitte warten Sie, bevor Sie eine weitere Produktfrage senden.');$recent[]=$now;$all[$key]=$recent;$session->set('com_fdshop.product_question.rate',$all);
    }

    private function companyEmail():string
    {
        $q=$this->db->getQuery(true)->select('document_company_email')->from($this->db->quoteName('#__fdshop_config'))->where('id=1');$this->db->setQuery($q);$email=(string)$this->db->loadResult();return filter_var($email,FILTER_VALIDATE_EMAIL)?$email:(string)Factory::getApplication()->get('mailfrom');
    }
}
