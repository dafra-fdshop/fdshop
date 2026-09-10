<?php

namespace FDShop\Component\FDShop\Site\Controller;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Service\CartServiceInterface;
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
            return $service->addItem($userId, $sessionId, $input->post->getInt('product_id'), $input->post->getFloat('quantity', 1));
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

    public function orderUnavailable(): void
    {
        $this->mutate(function (CartServiceInterface $service, int $userId, string $sessionId, int $shipmentId, int $paymentId): array {
            if ($userId < 1) {
                throw new \DomainException('Bitte melden Sie sich an oder registrieren Sie sich, um die Bestellung abzuschließen.');
            }
            $cart = $service->getCart($userId, $sessionId, $shipmentId, $paymentId);
            $cart['order_created'] = false;
            return $cart;
        }, 'Die Bestellfunktion wird in einem folgenden Paket aktiviert.');
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
            $data = $callback(
                $service,
                $userId,
                $session->getId(),
                (int) $session->get($this->sessionKey($userId, 'shipment_id'), 0),
                (int) $session->get($this->sessionKey($userId, 'payment_id'), 0)
            );
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

    private function sessionKey(int $userId, string $field): string
    {
        return 'com_fdshop.cart.' . $userId . '.' . $field;
    }

    private function normaliseState(array $cart): array
    {
        $format = static fn (float $value, string $currency): string => number_format($value, 2, ',', '.') . ' ' . (strtoupper($currency) === 'EUR' ? '€' : strtoupper($currency));
        $currency = (string) ($cart['currency'] ?? 'EUR');
        return [
            'items' => array_map(static fn ($item): array => ['id' => (int) $item->id, 'quantity' => (float) $item->quantity, 'unitPrice' => $format((float) $item->unit_price, (string) $item->currency), 'lineTotal' => $format((float) $item->line_total, (string) $item->currency)], $cart['items']),
            'subtotal' => $format((float) $cart['subtotal'], $currency),
            'shipmentFee' => $format((float) $cart['shipment_fee'], $currency),
            'paymentFee' => $format((float) $cart['payment_fee'], $currency),
            'total' => $format((float) $cart['total'], $currency),
            'shipmentId' => (int) ($cart['shipment']->id ?? 0),
            'shipmentName' => (string) ($cart['shipment']->name ?? 'Keine aktive Abholstation'),
            'paymentId' => (int) ($cart['payment']->id ?? 0),
            'paymentName' => (string) ($cart['payment']->name ?? 'Keine aktive Zahlungsart'),
            'orderCreated' => (bool) ($cart['order_created'] ?? false),
        ];
    }
}
