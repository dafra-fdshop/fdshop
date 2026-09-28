<?php
namespace FDShop\Component\FDShop\Site\Model;
defined('_JEXEC') or die;
use FDShop\Component\FDShop\Site\Service\AccountServiceInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
final class AccountModel extends BaseDatabaseModel
{
    public function getAccount():array{$app=Factory::getApplication();$id=(int)$app->getIdentity()->id;if($id<1)return [];$orderId=$app->getInput()->getInt('order_id')?:null;return $this->service()->dashboard($id,$app->getInput()->getInt('page',1),$orderId);}
    private function service():AccountServiceInterface{return Factory::getApplication()->bootComponent('com_fdshop')->getContainer()->get(AccountServiceInterface::class);}
}
