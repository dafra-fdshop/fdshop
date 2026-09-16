<?php
namespace FDShop\Component\FDShop\Administrator\Model;
defined('_JEXEC') or die;
use FDShop\Component\FDShop\Administrator\Service\FilterServiceInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
final class FilterModel extends AdminModel
{
    public function getTable($name='Filter',$prefix='Table',$options=[]){ return parent::getTable('Configuration',$prefix,$options); }
    public function getForm($data=[],$loadData=true): Form|false { return $this->loadForm('com_fdshop.filter','filter',['control'=>'jform','load_data'=>$loadData]); }
    protected function loadFormData(){ return $this->getItem(); }
    public function getItem($pk=null){ $id=$pk!==null?(int)$pk:Factory::getApplication()->getInput()->getInt('id'); return $this->service()->getFilter($id) ?: (object)[]; }
    public function save($data): bool { try { $id=$this->service()->save((array)$data); $this->setState($this->getName().'.id',$id); return true; } catch(\Throwable $e){$this->setError($e->getMessage());return false;} }
    private function service(): FilterServiceInterface { return $this->bootComponent('com_fdshop')->getContainer()->get(FilterServiceInterface::class); }
}
