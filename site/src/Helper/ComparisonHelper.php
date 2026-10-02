<?php
namespace FDShop\Component\FDShop\Site\Helper;
defined('_JEXEC') or die;
use FDShop\Component\FDShop\Site\Service\ComparisonService;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
final class ComparisonHelper{public static function service():ComparisonService{return new ComparisonService(Factory::getContainer()->get(DatabaseInterface::class));}}
