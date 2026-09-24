<?php

namespace FDShop\Component\FDShop\Site\Controller;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Service\CartServiceInterface;
use FDShop\Component\FDShop\Site\Service\CheckoutServiceInterface;
use FDShop\Component\FDShop\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

final class CartController extends BaseController
{
    public function add(): void
    {
        $this->mutate(function (CartServiceInterface $service, int $userId, string $sessionId): array {
            $input = Factory::getApplication()->getInput();
            return $service->addItem($userId, $sessionId, $input->post->getInt('product_id'), $input->post->getFloat('quantity', 1), $input->post->getCmd('unit_variant', 'piece'));
        });
    }

    public function updateQuantity(): void
    {
        $this->mutate(function (CartServiceInterface $service, int $userId, string $sessionId, int $shipmentId, int $paymentId): array {
            $input = Factory::getApplication()->getInput();
            return $service->updateQuantity($userId, $sessionId, $input->post->getInt('cart_id'), $input->post->getFloat('quantity'), $shipmentId, $paymentId);
        });
    }

    public function remove(): void
    {
        $this->mutate(function (CartServiceInterface $service, int $userId, string $sessionId, int $shipmentId, int $paymentId): array {
            return $service->removeItem($userId, $sessionId, Factory::getApplication()->getInput()->post->getInt('cart_id'), $shipmentId, $paymentId);
        });
    }

    public function selectShipment(): void
    {
        $this->mutate(function (CartServiceInterface $service, int $userId, string $sessionId, int $shipmentId, int $paymentId): array {
            $shipmentId = $service->validateShipment(Factory::getApplication()->getInput()->post->getInt('shipment_id'));
            Factory::getApplication()->getSession()->set($this->sessionKey($userId, 'shipment_id'), $shipmentId);
            return $service->getCart($userId, $sessionId, $shipmentId, $paymentId);
        });
    }

    public function selectPayment(): void
    {
        $this->mutate(function (CartServiceInterface $service, int $userId, string $sessionId, int $shipmentId, int $paymentId): array {
            $paymentId = $service->validatePayment(Factory::getApplication()->getInput()->post->getInt('payment_id'));
            Factory::getApplication()->getSession()->set($this->sessionKey($userId, 'payment_id'), $paymentId);
            return $service->getCart($userId, $sessionId, $shipmentId, $paymentId);
        });
    }

    public function applyCoupon(): void
    {
        $this->mutate(function (CartServiceInterface $service, int $userId, string $sessionId, int $shipmentId, int $paymentId): array {
            $app = Factory::getApplication();
            $couponCode = $app->getInput()->post->getString('coupon_code');
            $cart = $service->validateCoupon($userId, $sessionId, $couponCode, $shipmentId, $paymentId);
            $app->getSession()->set($this->sessionKey($userId, 'coupon_code'), $cart['coupon_code']);
            return $cart;
        }, 'Gutschein wurde berücksichtigt.');
    }

    public function checkout(): void
    {
        $app=Factory::getApplication();
        try {
            if(!Session::checkToken('request')) throw new \RuntimeException('Ungültiger Sicherheitstoken.');
            $userId=(int)$app->getIdentity()->id; $session=$app->getSession(); $input=$app->getInput();
            $result=$this->getCheckoutService()->createOrder($userId,$session->getId(),(int)$session->get($this->sessionKey($userId,'shipment_id'),0),(int)$session->get($this->sessionKey($userId,'payment_id'),0),(string)$session->get($this->sessionKey($userId,'coupon_code'),''),$input->post->getString('order_note'),$input->post->getInt('terms_accepted')===1,$input->post->getString('submission_id'));
            foreach(['shipment_id','payment_id','coupon_code'] as $field)$session->clear($this->sessionKey($userId,$field));
            echo new JsonResponse($result,$result['already_processed']?'Die Bestellung wurde bereits verarbeitet.':'Vielen Dank. Ihre Bestellung wurde erfolgreich angelegt.');
        } catch(\Throwable $error){echo new JsonResponse(null,$error->getMessage(),true);}
        $app->close();
    }

    private function mutate(callable $callback, string $successMessage = ''): void
    {
        $app = Factory::getApplication();
        try {
            if (!Session::checkToken('request')) {
                throw new \RuntimeException('Ungültiger Sicherheitstoken.');
            }
            $userId = (int) $app->getIdentity()->id;
            $session = $app->getSession();
            $service = $this->getCartService();
            $shipmentId = (int) $session->get($this->sessionKey($userId, 'shipment_id'), 0);
            $paymentId = (int) $session->get($this->sessionKey($userId, 'payment_id'), 0);
            $couponCode = (string) $session->get($this->sessionKey($userId, 'coupon_code'), '');
            $data = $callback(
                $service,
                $userId,
                $session->getId(),
                $shipmentId,
                $paymentId,
                $couponCode
            );
            if ($couponCode !== '' && ($data['coupon_code'] ?? '') === '') {
                try {
                    $data = $service->getCart(
                        $userId,
                        $session->getId(),
                        (int) ($data['shipment']->id ?? $shipmentId),
                        (int) ($data['payment']->id ?? $paymentId),
                        $couponCode
                    );
                    if (($data['coupon_code'] ?? '') === '') {
                        $session->set($this->sessionKey($userId, 'coupon_code'), '');
                    }
                } catch (\DomainException) {
                    $session->set($this->sessionKey($userId, 'coupon_code'), '');
                }
            }
            echo new JsonResponse($this->normaliseState($data), $successMessage);
        } catch (\Throwable $error) {
            echo new JsonResponse(null, $error->getMessage(), true);
        }
        $app->close();
    }

    private function getCartService(): CartServiceInterface
    {
        return Factory::getApplication()->bootComponent('com_fdshop')->getContainer()->get(CartServiceInterface::class);
    }

    private function getCheckoutService(): CheckoutServiceInterface
    {
        return Factory::getApplication()->bootComponent('com_fdshop')->getContainer()->get(CheckoutServiceInterface::class);
    }

    private function sessionKey(int $userId, string $field): string
    {
        return 'com_fdshop.cart.' . $userId . '.' . $field;
    }

    private function normaliseState(array $cart): array
    {
        $format = static fn (float $value, string $currency): string => number_format($value, 2, ',', '.') . ' ' . (strtoupper($currency) === 'EUR' ? '€' : strtoupper($currency));
        $currency = (string) ($cart['currency'] ?? 'EUR');
        $state = [
            'items' => array_map(static fn ($item): array => ['id' => (int) $item->id, 'quantity' => (float) $item->quantity, 'unitVariant' => (string) $item->unit_variant, 'unitPrice' => $format((float) $item->unit_price, (string) $item->currency), 'lineTotal' => $format((float) $item->line_total, (string) $item->currency)], $cart['items']),
            'bundles' => array_map(static fn ($bundle): array => ['id' => (int) $bundle->id, 'total' => $format((float) $bundle->total_gross, (string) $bundle->currency)], $cart['bundles'] ?? []),
            'subtotal' => $format((float) $cart['subtotal'], $currency),
            'shipmentFee' => $format((float) $cart['shipment_fee'], $currency),
            'paymentFee' => $format((float) $cart['payment_fee'], $currency),
            'couponCode' => (string) ($cart['coupon_code'] ?? ''),
            'couponDiscount' => $format((float) ($cart['coupon_discount'] ?? 0), $currency),
            'total' => $format((float) $cart['total'], $currency),
            'shipmentId' => (int) ($cart['shipment']->id ?? 0),
            'shipmentName' => (string) ($cart['shipment']->name ?? 'Keine aktive Abholstation'),
            'paymentId' => (int) ($cart['payment']->id ?? 0),
            'paymentName' => (string) ($cart['payment']->name ?? 'Keine aktive Zahlungsart'),
            'orderCreated' => (bool) ($cart['order_created'] ?? false),
        ];
        if (isset($cart['purchase'])) {
            $purchase = $cart['purchase'];
            $state['purchase'] = [
                'productId' => (int) $purchase['product_id'],
                'productName' => (string) $purchase['product_name'],
                'unitVariant' => (string) $purchase['unit_variant'],
                'unitType' => (string) $purchase['unit_type'],
                'requestedQuantity' => (float) $purchase['requested_quantity'],
                'effectiveQuantity' => (float) $purchase['effective_quantity'],
                'resultingCartQuantity' => (float) $purchase['resulting_cart_quantity'],
                'adjusted' => (bool) $purchase['adjusted'],
                'message' => (string) $purchase['message'],
                'unitPrice' => $format((float) $purchase['unit_price'], $currency),
                'lineAmount' => $format((float) $purchase['line_amount'], $currency),
                'cartUrl' => RouteHelper::getCartRoute(),
            ];
        }
        return $state;
    }
}
