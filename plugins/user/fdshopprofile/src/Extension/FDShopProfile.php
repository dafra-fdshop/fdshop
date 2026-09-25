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
        FormHelper::addFormPath(JPATH_PLUGINS . '/user/fdshopprofile/forms');
        $form->loadFile('fdshopprofile');
        if (in_array($form->getName(), ['com_users.registration', 'com_users.profile'], true)) {
            $form->setFieldAttribute('name', 'type', 'hidden');
            $form->setFieldAttribute('name', 'label', '');
        } elseif ($form->getName() === 'com_users.user') {
            // Existing Joomla accounts may legitimately predate FDShop customer profiles.
            // Administrators can keep editing those accounts; checkout remains the hard gate.
            foreach (self::REQUIRED as $field) {
                $form->setFieldAttribute($field, 'required', 'false', self::GROUP);
            }
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
        foreach (self::REQUIRED as $field) {
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
        $query = $db->getQuery(true)->delete($db->quoteName('#__user_profiles'))
            ->where($db->quoteName('user_id') . ' = :userId')
            ->where($db->quoteName('profile_key') . ' LIKE ' . $db->quote(self::GROUP . '.%'))
            ->bind(':userId', $userId, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
        $ordering = 100;
        foreach (self::FIELDS as $field) {
            $value = trim((string) ($data[self::GROUP][$field] ?? ''));
            $profileKey = self::GROUP . '.' . $field;
            $profileValue = json_encode($value, JSON_UNESCAPED_UNICODE);
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
}
