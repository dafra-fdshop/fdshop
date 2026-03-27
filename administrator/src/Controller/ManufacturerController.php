<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;

class ManufacturerController extends FormController
{
    protected $view_list = 'manufacturers';

    public function save($key = null, $urlVar = null)
    {
        $data = $this->input->post->get('jform', [], 'array');
        $model = $this->getModel();
        $task = $this->getTask();

        if (!$model->save($data)) {
            $this->setMessage($model->getError(), 'error');

            $id = (int) ($data['id'] ?? 0);
            $redirect = 'index.php?option=com_fdshop&view=manufacturer&layout=edit';

            if ($id > 0) {
                $redirect .= '&id=' . $id;
            }

            $this->setRedirect($redirect);

            return false;
        }

        $id = (int) $model->getState($model->getName() . '.id');

        $this->setMessage('Hersteller gespeichert.');

        if ($task === 'apply') {
            $this->setRedirect(
                'index.php?option=com_fdshop&view=manufacturer&layout=edit&id=' . $id
            );

            return true;
        }

        $this->setRedirect('index.php?option=com_fdshop&view=manufacturers');

        return true;
    }
}