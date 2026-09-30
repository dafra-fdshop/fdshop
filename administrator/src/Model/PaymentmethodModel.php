<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use FDShop\Component\FDShop\Site\Service\CredentialResolver;
use FDShop\Component\FDShop\Site\Service\CredentialStore;
use Joomla\Database\DatabaseInterface;

class PaymentmethodModel extends AdminModel
{
    protected $text_prefix = 'COM_FDSHOP_PAYMENTMETHOD';

    public function getTable($name = 'Paymentmethod', $prefix = 'Table', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true): Form|false
    {
        $form = $this->loadForm(
            'com_fdshop.paymentmethod',
            'paymentmethod',
            [
                'control'   => 'jform',
                'load_data' => $loadData,
            ]
        );

        if (!$form) {
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $app = Factory::getApplication();
        $data = $app->getUserState('com_fdshop.edit.paymentmethod.data', []);

        if (!empty($data)) {
            return $data;
        }

        $item = $this->getItem();
        if ($item) {
            $item->provider = (string) ($item->provider ?? '') ?: ((int) ($item->paypal_enabled ?? 0) === 1 ? 'paypal' : '');
            $resolver = new CredentialResolver();
            $item->paypal_mode = $resolver->paypalMode();
            foreach (['sandbox', 'live'] as $mode) {
                $item->{$mode . '_client_id'} = $resolver->paypalForMode($mode, 'client_id');
                $item->{$mode . '_client_secret'} = '';
                $item->{$mode . '_webhook_id'} = $resolver->paypalForMode($mode, 'webhook_id');
            }
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)->select('paypal_reservation_minutes')->from($db->quoteName('#__fdshop_config'))->where('id=1');
            $db->setQuery($query);
            $item->paypal_reservation_minutes = max(5, min(30, (int) $db->loadResult() ?: 10));
        }
        return $item;
    }

    public function save($data): bool
    {
        $data['provider'] = strtolower(trim((string) ($data['provider'] ?? '')));
        $data['paypal_enabled'] = $data['provider'] === 'paypal' ? 1 : 0;

        if ($data['provider'] === 'paypal' && array_key_exists('paypal_configuration_submitted', $data)) {
            if (!Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_fdshop')) {
                $this->setError('Nur Super User dürfen PayPal-Zugangsdaten verwalten.');
                return false;
            }
            $resolver = new CredentialResolver();
            $targetMode = strtolower(trim((string) ($data['paypal_mode'] ?? 'sandbox')));
            if ($resolver->modeSource() === 'environment') {
                $targetMode = $resolver->paypalMode();
            }
            if ($targetMode === 'live' && $resolver->paypalMode() !== 'live' && (int) ($data['paypal_live_confirmation'] ?? 0) !== 1) {
                $this->setError('Die Aktivierung von PayPal Live muss ausdrücklich bestätigt werden.');
                return false;
            }
            try {
                (new CredentialStore($resolver))->update([
                    'mode' => $targetMode,
                    'sandbox_client_id' => $data['sandbox_client_id'] ?? '',
                    'sandbox_client_secret' => $data['sandbox_client_secret'] ?? '',
                    'sandbox_webhook_id' => $data['sandbox_webhook_id'] ?? '',
                    'live_client_id' => $data['live_client_id'] ?? '',
                    'live_client_secret' => $data['live_client_secret'] ?? '',
                    'live_webhook_id' => $data['live_webhook_id'] ?? '',
                ]);
                $minutes = max(5, min(30, (int) ($data['paypal_reservation_minutes'] ?? 10)));
                $db = Factory::getContainer()->get(DatabaseInterface::class);
                $query = $db->getQuery(true)->update($db->quoteName('#__fdshop_config'))
                    ->set($db->quoteName('paypal_reservation_minutes') . '=' . $minutes)->where('id=1');
                $db->setQuery($query)->execute();
            } catch (\Throwable $exception) {
                $this->setError($exception->getMessage());
                return false;
            }
        }

        foreach (['paypal_configuration_submitted', 'paypal_mode', 'paypal_live_confirmation', 'paypal_reservation_minutes',
            'sandbox_client_id', 'sandbox_client_secret', 'sandbox_webhook_id', 'live_client_id', 'live_client_secret', 'live_webhook_id'] as $field) {
            unset($data[$field]);
        }
        $result = parent::save($data);

        if (!$result) {
            return false;
        }

        $id = (int) $this->getState($this->getName() . '.id');

        if ($id <= 0) {
            $table = $this->getTable();

            if (!empty($table->id)) {
                $id = (int) $table->id;
            }
        }

        if ($id <= 0 && !empty($data['id'])) {
            $id = (int) $data['id'];
        }

        if ($id > 0) {
            $this->setState($this->getName() . '.id', $id);
        }

        return true;
    }

    public function getPayPalAdminData(): array
    {
        $resolver = new CredentialResolver();
        $store = new CredentialStore($resolver);
        $result = ['mode' => $resolver->paypalMode(), 'mode_source' => $resolver->modeSource(), 'store' => $store->status(), 'modes' => []];
        foreach (['sandbox', 'live'] as $mode) {
            $result['modes'][$mode] = [];
            foreach (['client_id', 'client_secret', 'webhook_id'] as $name) {
                $result['modes'][$mode][$name] = [
                    'configured' => $resolver->paypalForMode($mode, $name) !== '',
                    'source' => $resolver->paypalSource($mode, $name),
                ];
            }
        }
        return $result;
    }
}
