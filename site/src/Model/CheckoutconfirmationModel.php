<?php
namespace FDShop\Component\FDShop\Site\Model;
defined('_JEXEC') or die;
use FDShop\Component\FDShop\Site\Service\CheckoutServiceInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
final class CheckoutconfirmationModel extends BaseDatabaseModel
{
    public function getConfirmation():?array{$app=Factory::getApplication();return $this->service()->getConfirmation((int)$app->getIdentity()->id,$app->getInput()->getString('order_number'));}
    private function service():CheckoutServiceInterface{return $this->bootComponent('com_fdshop')->getContainer()->get(CheckoutServiceInterface::class);}
}
