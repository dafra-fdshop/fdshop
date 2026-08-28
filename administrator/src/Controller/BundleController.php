<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Controller;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Administrator\Service\BundleServiceInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

class BundleController extends FormController
{
    protected $view_list = 'bundles';

    public function save($key = null, $urlVar = null)
    {
        $this->checkToken();

        $data  = $this->input->post->get('jform', [], 'array');
        $model = $this->getModel();
        $task  = $this->getTask();
        $id    = (int) ($data['id'] ?? 0);
        $action = $id > 0 ? 'core.edit' : 'core.create';

        if (!Factory::getApplication()->getIdentity()->authorise($action, 'com_fdshop')) {
            $this->setMessage('Sie sind nicht berechtigt, dieses Bundle zu speichern.', 'error');
            $this->setRedirect(
                $id > 0
                    ? 'index.php?option=com_fdshop&view=bundle&layout=edit&id=' . $id
                    : 'index.php?option=com_fdshop&view=bundles'
            );

            return false;
        }

        if (!$model->save($data)) {
            $this->setMessage($model->getError(), 'error');

            $redirect = 'index.php?option=com_fdshop&view=bundle&layout=edit';

            if ($id > 0) {
                $redirect .= '&id=' . $id;
            }

            $this->setRedirect($redirect);

            return false;
        }

        $id = (int) $model->getState($model->getName() . '.id');

        $this->setMessage('Bundle gespeichert.');

        if ($task === 'apply') {
            $this->setRedirect(
                'index.php?option=com_fdshop&view=bundle&layout=edit&id=' . $id
            );

            return true;
        }

        $this->setRedirect('index.php?option=com_fdshop&view=bundles');

        return true;
    }

    public function lookupProduct(): void
    {
        $app = Factory::getApplication();

        if (!$app->isClient('administrator')) {
            echo new JsonResponse(null, 'Ungültiger Zugriff.', true);
            $app->close();
        }

        $user = $app->getIdentity();

        if (!$user || (int) $user->id <= 0) {
            echo new JsonResponse(null, 'Benutzer ist nicht angemeldet.', true);
            $app->close();
        }

        if (!$user->authorise('core.manage', 'com_fdshop')) {
            echo new JsonResponse(null, 'Keine Berechtigung.', true);
            $app->close();
        }

        if (!Session::checkToken('request')) {
            echo new JsonResponse(null, 'Ungültiger Token.', true);
            $app->close();
        }

        $sku = trim((string) $this->input->getString('sku', ''));

        if ($sku === '') {
            echo new JsonResponse(null, 'Keine Artikelnummer angegeben.', true);
            $app->close();
        }

        try {
            $product = $this->getBundleService()->findProductBySku($sku);

            if (!$product) {
                echo new JsonResponse(null, 'Produkt wurde nicht gefunden.', true);
                $app->close();
            }

            echo new JsonResponse([
                'product_id'   => (int) $product->product_id,
                'product_name' => (string) $product->product_name,
                'sku'          => (string) $product->sku,
                'current_sale_price' => isset($product->current_sale_price) ? (float) $product->current_sale_price : null,
                'currency'     => (string) ($product->currency ?? 'EUR'),
                'is_active'    => (int) ($product->is_active ?? 0),
            ]);
        } catch (\Throwable $e) {
            echo new JsonResponse(null, $e->getMessage(), true);
        }

        $app->close();
    }

    public function searchProducts(): void
    {
        $app = Factory::getApplication();

        if (!$app->isClient('administrator')) {
            echo new JsonResponse(null, 'Ungültiger Zugriff.', true);
            $app->close();
        }

        $user = $app->getIdentity();

        if (!$user || (int) $user->id <= 0) {
            echo new JsonResponse(null, 'Benutzer ist nicht angemeldet.', true);
            $app->close();
        }

        if (!$user->authorise('core.manage', 'com_fdshop')) {
            echo new JsonResponse(null, 'Keine Berechtigung.', true);
            $app->close();
        }

        if (!Session::checkToken('request')) {
            echo new JsonResponse(null, 'Ungültiger Token.', true);
            $app->close();
        }

        $skuPrefix = trim((string) $this->input->getString('q', ''));

        try {
            $matches = $this->getBundleService()->searchProductsBySkuPrefix($skuPrefix, 15);
            $results = [];

            foreach ($matches as $match) {
                $results[] = [
                    'product_id'   => (int) $match->product_id,
                    'product_name' => (string) $match->product_name,
                    'sku'          => (string) $match->sku,
                    'is_active'    => (int) $match->is_active,
                ];
            }

            echo new JsonResponse($results);
        } catch (\Throwable $e) {
            echo new JsonResponse(null, $e->getMessage(), true);
        }

        $app->close();
    }

    private function getBundleService(): BundleServiceInterface
    {
        $component = Factory::getApplication()->bootComponent('com_fdshop');
        $container = $component->getContainer();

        return $container->get(BundleServiceInterface::class);
    }
}
