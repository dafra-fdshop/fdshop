<?php
namespace FDShop\Component\FDShop\Site\Model;
defined('_JEXEC') or die;
use FDShop\Component\FDShop\Site\Helper\ComparisonHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
final class ComparisonModel extends BaseDatabaseModel{public function getData():array{$service=ComparisonHelper::service();$user=(int)Factory::getApplication()->getIdentity()->id;return['state'=>$service->state(),'items'=>$service->products(),'saved'=>$user?$service->saved($user):[],'user_id'=>$user];}}
