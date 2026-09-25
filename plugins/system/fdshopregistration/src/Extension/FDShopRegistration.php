<?php
namespace FDShop\Plugin\System\FDShopRegistration\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Application\AfterDispatchEvent;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

final class FDShopRegistration extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array { return ['onAfterDispatch' => 'afterDispatch']; }

    public function afterDispatch(AfterDispatchEvent $event): void
    {
        $app = $event->getApplication();
        if (!$app->isClient('site') || $app->getIdentity()->id || $app->getDocument()->getType() !== 'html'
            || $app->getInput()->getCmd('option') !== 'com_users') return;
        $document = $app->getDocument();
        $registration = $document->getBuffer('component');
        if (!str_contains($registration, 'id="member-registration"') || str_contains($registration, 'fdshop-account-entry')) return;

        $this->loadLanguage();
        $login = ModuleHelper::renderModule(ModuleHelper::getModule('mod_login', 'FDShop registration login'), ['style' => 'none']);
        $assets = $document->getWebAssetManager();
        $assets->getRegistry()->addRegistryFile('media/com_fdshop/joomla.asset.json');
        $assets->useStyle('com_fdshop.site');
        $document->setBuffer(
            '<div class="fdshop-registration-note" role="note">' . Text::_('PLG_SYSTEM_FDSHOPREGISTRATION_NOTE') . '</div>'
            . '<div class="fdshop-account-entry">'
            . '<section class="fdshop-account-entry__login" aria-labelledby="fdshop-login-title"><h2 id="fdshop-login-title">' . Text::_('PLG_SYSTEM_FDSHOPREGISTRATION_LOGIN_TITLE') . '</h2>' . $login . '</section>'
            . '<section class="fdshop-account-entry__registration" aria-labelledby="fdshop-registration-title"><h2 id="fdshop-registration-title">' . Text::_('PLG_SYSTEM_FDSHOPREGISTRATION_TITLE') . '</h2>' . $registration . '</section>'
            . '</div>', 'component');
    }
}
