<?php
namespace FDShop\Plugin\Task\PaymentCleanup\Extension;
defined('_JEXEC') or die;
use FDShop\Component\FDShop\Site\Service\PaymentServiceInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;
final class PaymentCleanup extends CMSPlugin implements SubscriberInterface
{
 use TaskPluginTrait;
 private const TASKS_MAP=['fdshop.payment_cleanup'=>['langConstPrefix'=>'PLG_TASK_FDSHOP_PAYMENT_CLEANUP','method'=>'cleanup','form'=>'']];
 protected $autoloadLanguage=true;
 public static function getSubscribedEvents():array{return ['onTaskOptionsList'=>'advertiseRoutines','onExecuteTask'=>'standardRoutineHandler'];}
 private function cleanup(ExecuteTaskEvent $event):int{try{$count=$this->getApplication()->bootComponent('com_fdshop')->getContainer()->get(PaymentServiceInterface::class)->cleanupExpired();$this->logTask('FDShop: '.$count.' abgelaufene Zahlungsreservierung(en) bereinigt.','info');return Status::OK;}catch(\Throwable $e){$this->logTask('FDShop Payment Cleanup fehlgeschlagen: '.$e->getMessage(),'error');return Status::KNOCKOUT;}}
}
