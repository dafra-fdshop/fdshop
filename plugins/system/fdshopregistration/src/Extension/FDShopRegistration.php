<?php
namespace FDShop\Plugin\System\FDShopRegistration\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Application\AfterDispatchEvent;
use Joomla\CMS\Event\Application\AfterRouteEvent;
use Joomla\CMS\Event\User\AfterLoginEvent;
use FDShop\Component\FDShop\Site\Service\CartContinuationServiceInterface;
use FDShop\Component\FDShop\Site\Service\CartServiceInterface;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\SubscriberInterface;

final class FDShopRegistration extends CMSPlugin implements SubscriberInterface
{
    private const SESSION_REDIRECT_TARGET = 'com_fdshop.login.redirect_target';

    public static function getSubscribedEvents(): array { return ['onAfterRoute' => 'afterRoute', 'onUserAfterLogin' => 'afterLogin', 'onAfterDispatch' => 'afterDispatch']; }

    public function afterRoute(AfterRouteEvent $event): void
    {
        $app = $event->getApplication();
        if (!$app->isClient('site')) return;
        $session = $app->getSession();
        if (!$app->getIdentity()->guest) {
            $redirectTarget = (string) $session->get(self::SESSION_REDIRECT_TARGET, '');
            if ($redirectTarget !== '') {
                $session->clear(self::SESSION_REDIRECT_TARGET);
                $session->clear('com_fdshop.login.pending');
                $session->clear('com_fdshop.login.safe_return');
                $session->clear('com_fdshop.cart_continuation.intent');
                if (Uri::isInternal($redirectTarget) && !$this->isCurrentFdshopTarget($redirectTarget)) {
                    $app->redirect($redirectTarget);
                }
                return;
            }
            if ($app->getInput()->getCmd('task') === 'cart.chooseCart') return;
            $token = $this->token();
            if ($token !== '') {
                try {
                    $userId = (int) $app->getIdentity()->id;
                    $result = $this->service()->resolveAfterLogin($token, $userId, ['shipment_id' => (int) $session->get('com_fdshop.cart.' . $userId . '.shipment_id', 0), 'payment_id' => (int) $session->get('com_fdshop.cart.' . $userId . '.payment_id', 0), 'coupon_code' => (string) $session->get('com_fdshop.cart.' . $userId . '.coupon_code', '')]);
                    if ($result['state'] !== 'conflict') $this->clearCookie();
                    $target = $result['state'] === 'conflict' || $result['target'] === 'checkout' ? 'index.php?option=com_fdshop&view=cart' : $this->normalLoginTarget();
                    $alreadyThere = $app->getInput()->getCmd('option') === 'com_fdshop' && $app->getInput()->getCmd('view') === ($result['state'] === 'conflict' || $result['target'] === 'checkout' ? 'cart' : 'account');
                    if (!$alreadyThere) {
                        $session->clear('com_fdshop.cart_continuation.intent');
                        $app->redirect($target);
                    }
                } catch (\Throwable $error) {
                    $app->enqueueMessage('Ihr Warenkorb blieb erhalten, konnte aber noch nicht übernommen werden: ' . $error->getMessage(), 'error');
                    if (!($app->getInput()->getCmd('option') === 'com_fdshop' && $app->getInput()->getCmd('view') === 'cart')) $app->redirect('index.php?option=com_fdshop&view=cart');
                }
                return;
            }
            if ($session->get('com_fdshop.login.pending') === 1) {
                $session->clear('com_fdshop.login.pending');
                if ($app->getInput()->getCmd('option') === 'com_users' && $app->getInput()->getCmd('view', 'profile') === 'profile') $app->redirect($this->normalLoginTarget());
            }
            return;
        }
        if ($app->getInput()->getCmd('option') !== 'com_users') return;
        $session->set('com_fdshop.login.pending', 1);
        $encodedReturn = $app->getInput()->getString('return', '');
        $requestedReturn = $encodedReturn !== '' ? base64_decode($encodedReturn, true) : false;
        if (is_string($requestedReturn) && $requestedReturn !== '' && Uri::isInternal($requestedReturn)
            && !str_contains($requestedReturn, 'option=com_users&view=profile') && !str_contains($requestedReturn, 'option=com_users&view=login')) {
            $session->set('com_fdshop.login.safe_return', $requestedReturn);
        }
        try {
            $cart = $app->bootComponent('com_fdshop')->getContainer()->get(CartServiceInterface::class)->getCart(0, $session->getId());
            if ($cart['items'] === [] && $cart['bundles'] === []) return;
            $intent = $app->getInput()->getBool('fdshop_checkout') || $session->get('com_fdshop.cart_continuation.intent') === 'checkout' ? 'checkout' : 'account';
            $metadata = ['shipment_id' => (int) $session->get('com_fdshop.cart.0.shipment_id', 0), 'payment_id' => (int) $session->get('com_fdshop.cart.0.payment_id', 0), 'coupon_code' => (string) $session->get('com_fdshop.cart.0.coupon_code', '')];
            $token = $this->service()->ensure($session->getId(), $intent, $metadata, $this->token());
            $session->set('com_fdshop.cart_continuation.intent', $intent);
            $this->setCookie($token);
        } catch (\Throwable) {
            // The login/registration page must remain available if FDShop is temporarily unavailable.
        }
    }

    public function afterLogin(AfterLoginEvent $event): void
    {
        $app = $this->getApplication();
        if (!$app->isClient('site')) return;
        $options = $event->getOptions();
        $userId = (int) ($options['user']->id ?? 0);
        if ($userId < 1) return;
        $session = $app->getSession();
        $token = $this->token();
        $result = ['state' => 'none', 'target' => 'account'];
        try {
            if ($token !== '') {
                $result = $this->service()->resolveAfterLogin($token, $userId, ['shipment_id' => (int) $session->get('com_fdshop.cart.' . $userId . '.shipment_id', 0), 'payment_id' => (int) $session->get('com_fdshop.cart.' . $userId . '.payment_id', 0), 'coupon_code' => (string) $session->get('com_fdshop.cart.' . $userId . '.coupon_code', '')]);
            }
        } catch (\Throwable $error) {
            $app->enqueueMessage('Ihr Warenkorb blieb erhalten, konnte aber noch nicht übernommen werden: ' . $error->getMessage(), 'error');
            $result = ['state' => 'conflict', 'target' => 'cart'];
        }
        if ($result['state'] !== 'conflict') $this->clearCookie();
        $explicit = (string) ($options['return'] ?? '');
        $safeReturn = (string) $session->get('com_fdshop.login.safe_return', '');
        $defaultReturn = $explicit === '' || str_contains($explicit, 'option=com_users&view=profile') || str_contains($explicit, 'option=com_users&view=login');
        if ($result['state'] === 'conflict' || $result['target'] === 'checkout') {
            $target = 'index.php?option=com_fdshop&view=cart';
        } elseif ($safeReturn !== '' && Uri::isInternal($safeReturn)) {
            $target = $safeReturn;
        } elseif (!$defaultReturn && Uri::isInternal($explicit)) {
            $target = $explicit;
        } else {
            $target = 'index.php?option=com_fdshop&view=account';
        }
        // Joomla stores controller user-state values inside its session registry;
        // writing the same-looking key directly to the session does not modify it.
        $app->setUserState('users.login.form.return', $target);
        // Joomla may first route to its profile/login destination after authentication.
        // Keep the resolved, internal FDShop target until that following request.
        $session->set(self::SESSION_REDIRECT_TARGET, $target);
    }

    public function afterDispatch(AfterDispatchEvent $event): void
    {
        $app = $event->getApplication();
        if (!$app->isClient('site') || $app->getIdentity()->id || $app->getDocument()->getType() !== 'html' || $app->getInput()->getCmd('option') !== 'com_users') return;
        $document = $app->getDocument();
        $registration = $document->getBuffer('component');
        $checkout = $app->getSession()->get('com_fdshop.cart_continuation.intent') === 'checkout' || $app->getInput()->getBool('fdshop_checkout');
        if ($app->getSession()->get('com_fdshop.cart_continuation.registered') === 1) {
            $app->getSession()->clear('com_fdshop.cart_continuation.registered');
        }
        $input = $app->getInput();
        $view = $input->getCmd('view');
        $task = $input->getCmd('task');
        if ($checkout && ($view === 'login' || $task === 'registration.activate')) {
            $login = $this->withLoginReturn($registration, 'index.php?option=com_fdshop&view=cart');
            if ($login !== $registration) $document->setBuffer($login, 'component');
            return;
        }
        if (($view !== 'registration' && $task !== 'registration.register') || $task === 'registration.activate') return;
        $hasRegistrationForm = str_contains($registration, 'id="member-registration"')
            && str_contains($registration, 'name="jform[email1]"');
        $hasJoomlaLogin = str_contains($registration, 'name="username"')
            && str_contains($registration, 'task=user.login');
        if (!$hasRegistrationForm || $hasJoomlaLogin || str_contains($registration, 'fdshop-account-entry')) return;

        $this->loadLanguage();
        $loginTarget = $checkout ? 'index.php?option=com_fdshop&view=cart' : 'index.php?option=com_fdshop&view=account';
        $login = $this->withLoginReturn(ModuleHelper::renderModule(ModuleHelper::getModule('mod_login', 'FDShop registration login'), ['style' => 'none']), $loginTarget);
        $assets = $document->getWebAssetManager();
        $assets->getRegistry()->addRegistryFile('media/com_fdshop/joomla.asset.json');
        $assets->useStyle('com_fdshop.site');
        $document->setBuffer(
            '<div class="fdshop-registration-note" role="note">' . ($checkout ? '<h1>' . Text::_('PLG_SYSTEM_FDSHOPREGISTRATION_CHECKOUT_TITLE') . '</h1><p>' . Text::_('PLG_SYSTEM_FDSHOPREGISTRATION_CHECKOUT_TEXT') . '</p>' : '<h1>' . Text::_('PLG_SYSTEM_FDSHOPREGISTRATION_NOTE_TITLE') . '</h1><p>' . Text::_('PLG_SYSTEM_FDSHOPREGISTRATION_NOTE') . '</p>') . '</div>'
            . '<div class="fdshop-account-entry">'
            . '<section class="fdshop-account-entry__login" aria-labelledby="fdshop-login-title"><h2 id="fdshop-login-title">' . Text::_('PLG_SYSTEM_FDSHOPREGISTRATION_LOGIN_TITLE') . '</h2>' . $login . '</section>'
            . '<section class="fdshop-account-entry__registration" aria-labelledby="fdshop-registration-title"><h2 id="fdshop-registration-title">' . Text::_('PLG_SYSTEM_FDSHOPREGISTRATION_TITLE') . '</h2>' . $registration . '</section>'
            . '</div>', 'component');
    }

    private function service(): CartContinuationServiceInterface
    {
        return $this->getApplication()->bootComponent('com_fdshop')->getContainer()->get(CartContinuationServiceInterface::class);
    }

    private function token(): string
    {
        return $this->getApplication()->getInput()->cookie->getString(CartContinuationServiceInterface::COOKIE_NAME, '');
    }

    private function setCookie(string $token): void
    {
        setcookie(CartContinuationServiceInterface::COOKIE_NAME, $token, ['expires' => time() + 7200, 'path' => '/', 'secure' => Uri::getInstance()->isSsl(), 'httponly' => true, 'samesite' => 'Lax']);
    }

    private function clearCookie(): void
    {
        setcookie(CartContinuationServiceInterface::COOKIE_NAME, '', ['expires' => 1, 'path' => '/', 'secure' => Uri::getInstance()->isSsl(), 'httponly' => true, 'samesite' => 'Lax']);
    }

    private function normalLoginTarget(): string
    {
        $session = $this->getApplication()->getSession();
        $target = (string) $session->get('com_fdshop.login.safe_return', '');
        $session->clear('com_fdshop.login.safe_return');
        return $target !== '' && Uri::isInternal($target) ? $target : 'index.php?option=com_fdshop&view=account';
    }

    private function isCurrentFdshopTarget(string $target): bool
    {
        $query = [];
        parse_str((string) parse_url($target, PHP_URL_QUERY), $query);
        if (($query['option'] ?? '') !== 'com_fdshop') return false;
        $input = $this->getApplication()->getInput();
        return $input->getCmd('option') === 'com_fdshop' && $input->getCmd('view') === (string) ($query['view'] ?? '');
    }

    private function withLoginReturn(string $html, string $target): string
    {
        if (!Uri::isInternal($target)) return $html;
        $encoded = base64_encode($target);
        $changed = false;
        $html = (string) preg_replace_callback('/<input\b[^>]*\bname=(["\'])return\1[^>]*>/i', static function (array $match) use ($encoded, &$changed): string {
            $changed = true;
            $tag = $match[0];
            if (preg_match('/\bvalue=(["\'])[^"\']*\1/i', $tag)) {
                return (string) preg_replace('/\bvalue=(["\'])[^"\']*\1/i', 'value="' . $encoded . '"', $tag, 1);
            }
            return substr($tag, 0, -1) . ' value="' . $encoded . '">';
        }, $html);
        if ($changed) return $html;
        return (string) preg_replace('/<\/form>/i', '<input type="hidden" name="return" value="' . $encoded . '"></form>', $html, 1);
    }
}
