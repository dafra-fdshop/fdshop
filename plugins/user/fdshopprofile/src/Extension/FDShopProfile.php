<?php
namespace FDShop\Plugin\User\FDShopProfile\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Model\BeforeValidateDataEvent;
use Joomla\CMS\Event\Model\PrepareDataEvent;
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Event\User\AfterDeleteEvent;
use Joomla\CMS\Event\User\AfterSaveEvent;
use Joomla\CMS\Event\User\BeforeSaveEvent;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;
use Joomla\Utilities\ArrayHelper;

final class FDShopProfile extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    private const GROUP = 'fdshop_customer';
    private const FIELDS = ['first_name', 'last_name', 'company', 'street', 'postal_code', 'city', 'country', 'phone'];
    private const REQUIRED = ['first_name', 'last_name', 'street', 'postal_code', 'city', 'country'];
    private const DEFAULT_STATUS = ['company' => 1, 'street' => 2, 'postal_code' => 2, 'city' => 2, 'country' => 2, 'phone' => 1];
    private const DEFAULT_ORDER = ['first_name' => 10, 'last_name' => 20, 'company' => 30, 'street' => 40, 'postal_code' => 50, 'city' => 60, 'country' => 70, 'phone' => 80];

    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepareData' => 'prepareData',
            'onContentPrepareForm' => 'prepareForm',
            'onContentBeforeValidateData' => 'beforeValidateData',
            'onUserBeforeSave' => 'beforeUserSave',
            'onUserAfterSave' => 'afterUserSave',
            'onUserAfterDelete' => 'afterUserDelete',
        ];
    }

    public function prepareForm(PrepareFormEvent $event): void
    {
        $form = $event->getForm();
        if (!in_array($form->getName(), ['com_users.registration', 'com_users.profile', 'com_users.user'], true)) return;
        $this->loadLanguage();
        FormHelper::addFormPath(JPATH_PLUGINS . '/user/fdshopprofile/forms');
        $form->loadFile('fdshopprofile');
        $this->configureFields($form);
        if (in_array($form->getName(), ['com_users.registration', 'com_users.profile'], true)) {
            $form->setFieldAttribute('name', 'type', 'hidden');
            $form->setFieldAttribute('name', 'label', '');
        }
        if ($form->getName() === 'com_users.registration') {
            $this->orderRegistrationCoreFields($form);
        }
    }

    public function prepareData(PrepareDataEvent $event): void
    {
        if (!in_array($event->getContext(), ['com_users.registration', 'com_users.profile', 'com_users.user'], true)) return;
        $data = $event->getData();
        if (!is_object($data) || isset($data->{self::GROUP})) return;
        $userId = (int) ($data->id ?? 0);
        $profile = array_fill_keys(self::FIELDS, '');
        $profile['country'] = 'Deutschland';
        if ($userId > 0) {
            $query = $this->getDatabase()->getQuery(true)
                ->select([$this->getDatabase()->quoteName('profile_key'), $this->getDatabase()->quoteName('profile_value')])
                ->from($this->getDatabase()->quoteName('#__user_profiles'))
                ->where($this->getDatabase()->quoteName('user_id') . ' = :userId')
                ->where($this->getDatabase()->quoteName('profile_key') . ' LIKE ' . $this->getDatabase()->quote(self::GROUP . '.%'))
                ->bind(':userId', $userId, ParameterType::INTEGER);
            $this->getDatabase()->setQuery($query);
            foreach ($this->getDatabase()->loadRowList() as [$key, $value]) {
                $field = substr((string) $key, strlen(self::GROUP) + 1);
                if (in_array($field, self::FIELDS, true)) $profile[$field] = (string) (json_decode($value, true) ?? $value);
            }
        }
        $data->{self::GROUP} = $profile;
    }

    public function beforeValidateData(BeforeValidateDataEvent $event): void
    {
        if (!in_array($event->getForm()->getName(), ['com_users.registration', 'com_users.profile', 'com_users.user'], true)) return;
        $data = (array) $event->getData();
        if (!isset($data[self::GROUP]) || !is_array($data[self::GROUP])) return;
        $data['name'] = $this->fullName($data[self::GROUP]);
        $event->updateData($data);
    }

    public function beforeUserSave(BeforeSaveEvent $event): void
    {
        $data = $event->getData();
        if (!isset($data[self::GROUP]) || !is_array($data[self::GROUP])) return;
        foreach ($this->requiredFields() as $field) {
            if (trim((string) ($data[self::GROUP][$field] ?? '')) === '') throw new \InvalidArgumentException('Bitte füllen Sie alle erforderlichen FDShop-Kundendaten aus.');
        }
        if ($this->fullName($data[self::GROUP]) === '') throw new \InvalidArgumentException('Vorname und Nachname sind erforderlich.');
    }

    public function afterUserSave(AfterSaveEvent $event): void
    {
        $data = $event->getUser();
        $userId = ArrayHelper::getValue($data, 'id', 0, 'int');
        if (!$event->getSavingResult() || $userId < 1 || !isset($data[self::GROUP]) || !is_array($data[self::GROUP])) return;
        $db = $this->getDatabase();
        $ordering = 100;
        foreach (self::FIELDS as $field) {
            if (!$this->isActive($field) || !array_key_exists($field, $data[self::GROUP])) continue;
            $value = trim((string) ($data[self::GROUP][$field] ?? ''));
            $profileKey = self::GROUP . '.' . $field;
            $profileValue = json_encode($value, JSON_UNESCAPED_UNICODE);
            $query = $db->getQuery(true)->delete($db->quoteName('#__user_profiles'))
                ->where($db->quoteName('user_id') . ' = :userId')
                ->where($db->quoteName('profile_key') . ' = :profileKey')
                ->bind(':userId', $userId, ParameterType::INTEGER)
                ->bind(':profileKey', $profileKey);
            $db->setQuery($query)->execute();
            $query = $db->getQuery(true)->insert($db->quoteName('#__user_profiles'))
                ->columns($db->quoteName(['user_id', 'profile_key', 'profile_value', 'ordering']))
                ->values(':userId, :profileKey, :profileValue, :ordering')
                ->bind(':userId', $userId, ParameterType::INTEGER)
                ->bind(':profileKey', $profileKey)
                ->bind(':profileValue', $profileValue)
                ->bind(':ordering', $ordering, ParameterType::INTEGER);
            $db->setQuery($query)->execute();
            $ordering++;
        }
    }

    public function afterUserDelete(AfterDeleteEvent $event): void
    {
        if (!$event->getDeletingResult()) return;
        $userId = ArrayHelper::getValue($event->getUser(), 'id', 0, 'int');
        if ($userId < 1) return;
        $query = $this->getDatabase()->getQuery(true)->delete($this->getDatabase()->quoteName('#__user_profiles'))
            ->where($this->getDatabase()->quoteName('user_id') . ' = :userId')
            ->where($this->getDatabase()->quoteName('profile_key') . ' LIKE ' . $this->getDatabase()->quote(self::GROUP . '.%'))
            ->bind(':userId', $userId, ParameterType::INTEGER);
        $this->getDatabase()->setQuery($query)->execute();
    }

    private function fullName(array $profile): string
    {
        return trim(trim((string) ($profile['first_name'] ?? '')) . ' ' . trim((string) ($profile['last_name'] ?? '')));
    }

    private function configureFields(\Joomla\CMS\Form\Form $form): void
    {
        $definitions = [];
        foreach (self::FIELDS as $field) {
            $xml = $form->getFieldXml($field, self::GROUP);
            if ($xml) $definitions[$field] = new \SimpleXMLElement($xml->asXML());
            $form->removeField($field, self::GROUP);
        }
        uasort($definitions, fn(\SimpleXMLElement $a, \SimpleXMLElement $b): int =>
            $this->fieldOrder((string) $a['name']) <=> $this->fieldOrder((string) $b['name']));
        foreach ($definitions as $field => $xml) {
            if (!$this->isActive($field)) continue;
            $xml['required'] = $this->fieldStatus($field) === 2 ? 'true' : 'false';
            $form->setField($xml, self::GROUP, true, self::GROUP);
        }
    }

    private function orderRegistrationCoreFields(\Joomla\CMS\Form\Form $form): void
    {
        $definitions = [];
        foreach (['email1', 'username', 'password1', 'password2'] as $field) {
            $xml = $form->getFieldXml($field);
            if ($xml) $definitions[$field] = new \SimpleXMLElement($xml->asXML());
            $form->removeField($field);
        }
        foreach ($definitions as $xml) $form->setField($xml, null, true, 'default');
    }

    private function fieldStatus(string $field): int
    {
        if (in_array($field, ['first_name', 'last_name'], true)) return 2;
        return (int) $this->params->get('field_' . $field, self::DEFAULT_STATUS[$field] ?? 1);
    }

    private function fieldOrder(string $field): int
    {
        return (int) $this->params->get('order_' . $field, self::DEFAULT_ORDER[$field] ?? 999);
    }

    private function isActive(string $field): bool { return $this->fieldStatus($field) > 0; }

    private function requiredFields(): array
    {
        return array_values(array_filter(self::FIELDS, fn(string $field): bool => $this->fieldStatus($field) === 2));
    }
}
