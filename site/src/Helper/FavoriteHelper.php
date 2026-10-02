<?php
namespace FDShop\Component\FDShop\Site\Helper;
defined('_JEXEC') or die;
use FDShop\Component\FDShop\Site\Service\FavoriteService;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
final class FavoriteHelper{public static function service():FavoriteService{return new FavoriteService(Factory::getContainer()->get(DatabaseInterface::class));}public static function viewState(array$items):array{$service=self::service();$enabled=$service->enabled();$user=(int)Factory::getApplication()->getIdentity()->id;$states=$enabled&&$user? $service->states($user,array_map(static fn($i)=>(int)$i->id,$items)):[];return['enabled'=>$enabled,'guest'=>$user<1,'states'=>$states];}}
