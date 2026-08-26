<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;

class CouponController extends FormController
{
    protected $view_list = 'coupons';

    public function save($key = null, $urlVar = null)
    {
        $data  = $this->input->post->get('jform', [], 'array');
        $model = $this->getModel();
        $task  = $this->getTask();

        if (!$model->save($data)) {
            $this->setMessage($model->getError(), 'error');

            $id       = (int) ($data['id'] ?? 0);
            $redirect = 'index.php?option=com_fdshop&view=coupon&layout=edit';

            if ($id > 0) {
                $redirect .= '&id=' . $id;
            }

            $this->setRedirect($redirect);

            return false;
        }

        $id = (int) $model->getState($model->getName() . '.id');

        $this->setMessage('Gutschein gespeichert.');

        if ($task === 'apply') {
            $this->setRedirect(
                'index.php?option=com_fdshop&view=coupon&layout=edit&id=' . $id
            );

            return true;
        }

        $this->setRedirect('index.php?option=com_fdshop&view=coupons');

        return true;
    }

    public function cancel($key = null): bool
    {
        parent::cancel($key);

        $this->setRedirect('index.php?option=com_fdshop&view=coupons');

        return true;
    }
}
