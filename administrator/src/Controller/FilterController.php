<?php
namespace FDShop\Component\FDShop\Administrator\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
final class FilterController extends FormController
{
    protected $view_list='filters';
    public function save($key=null,$urlVar=null)
    {
        $this->checkToken(); $data=$this->input->post->get('jform',[],'array'); $model=$this->getModel(); $id=(int)($data['id']??0);
        if (!Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_fdshop')) { throw new \RuntimeException('Keine Berechtigung zum Bearbeiten der Filter.'); }
        if(!$model->save($data)){ $this->setMessage($model->getError(),'error'); $this->setRedirect('index.php?option=com_fdshop&view=filter&layout=edit&id='.$id); return false; }
        $this->setMessage('Filterkonfiguration gespeichert.');
        $this->setRedirect($this->getTask()==='apply'?'index.php?option=com_fdshop&view=filter&layout=edit&id='.$id:'index.php?option=com_fdshop&view=filters'); return true;
    }
}
