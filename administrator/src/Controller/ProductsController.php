<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

class ProductsController extends AdminController
{
    protected $text_prefix = 'COM_FDSHOP_PRODUCTS';

    protected $default_view = 'products';

    public function getModel($name = 'Product', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function restore(): bool
    {
        return $this->executeProductAction('restore', 'Die ausgewählten Produkte wurden wiederhergestellt.');
    }

    public function deletePermanently(): bool
    {
        return $this->executeProductAction('deletePermanently', 'Die ausgewählten Produkte wurden endgültig gelöscht.');
    }

    private function executeProductAction(string $modelMethod, string $successMessage): bool
    {
        $this->checkToken();

        $ids = $this->input->post->get('cid', [], 'array');
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $redirect = 'index.php?option=com_fdshop&view=products&filter_deleted=1';

        if ($ids === []) {
            $this->setMessage('Es wurden keine Produkte ausgewählt.', 'warning');
            $this->setRedirect($redirect);

            return false;
        }

        $model = $this->getModel();

        if (!$model->$modelMethod($ids)) {
            $this->setMessage($model->getError(), 'error');
            $this->setRedirect($redirect);

            return false;
        }

        $this->setMessage($successMessage);
        $this->setRedirect($redirect);

        return true;
    }
}
