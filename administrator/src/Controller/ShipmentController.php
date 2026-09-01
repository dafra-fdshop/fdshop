<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;

class ShipmentController extends FormController
{
    protected $view_list = 'configuration';

    public function save($key = null, $urlVar = null)
    {
        $this->checkToken();

        $data = $this->input->post->get('jform', [], 'array');
        $model = $this->getModel();
        $task = $this->getTask();
        $id = (int) ($data['id'] ?? 0);
        $action = $id > 0 ? 'core.edit' : 'core.create';

        if (!Factory::getApplication()->getIdentity()->authorise($action, 'com_fdshop')) {
            $this->setMessage('Sie sind nicht berechtigt, diese Versandart zu speichern.', 'error');
            $this->setRedirect(
                $id > 0
                    ? 'index.php?option=com_fdshop&view=shipment&layout=edit&id=' . $id
                    : 'index.php?option=com_fdshop&view=configuration'
            );

            return false;
        }

        $form = $model->getForm($data, false);
        $validData = $form ? $model->validate($form, $data) : false;

        if ($validData === false) {
            Factory::getApplication()->setUserState('com_fdshop.edit.shipment.data', $data);
            $this->setMessage($model->getError() ?: 'Die Formulardaten sind ungültig.', 'error');
            $this->setRedirect(
                'index.php?option=com_fdshop&view=shipment&layout=edit' . ($id > 0 ? '&id=' . $id : '')
            );

            return false;
        }

        if (!$model->save($validData)) {
            $this->setMessage($model->getError(), 'error');

            $redirect = 'index.php?option=com_fdshop&view=shipment&layout=edit';

            if ($id > 0) {
                $redirect .= '&id=' . $id;
            }

            $this->setRedirect($redirect);

            return false;
        }

        $id = (int) $model->getState($model->getName() . '.id');
        Factory::getApplication()->setUserState('com_fdshop.edit.shipment.data', null);

        $this->setMessage('Versandart gespeichert.');

        if ($task === 'apply') {
            $this->setRedirect(
                'index.php?option=com_fdshop&view=shipment&layout=edit&id=' . $id
            );

            return true;
        }

        $this->setRedirect('index.php?option=com_fdshop&view=configuration');

        return true;
    }
}
