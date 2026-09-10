<?php

namespace FDShop\Component\FDShop\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class CartService implements CartServiceInterface
{
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    public function getCart(int $userId, string $sessionId, int $shipmentId = 0, int $paymentId = 0): array
    {
        $this->assertOwner($userId, $sessionId);
        $items = $this->loadItems($userId, $sessionId);
        $this->attachImages($items);
        $shipments = $this->loadChoices('shipments');
        $payments = $this->loadChoices('payment_methods');
        [$shipment, $shipmentFallback] = $this->resolveChoice($shipments, $shipmentId, 'Versandart');
        [$payment, $paymentFallback] = $this->resolveChoice($payments, $paymentId, 'Zahlungsart');
        $subtotal = 0.0;

        foreach ($items as $item) {
            $item->quantity = (float) $item->quantity;
            $item->min_order_qty = (float) $item->min_order_qty > 0 ? (float) $item->min_order_qty : 1.0;
            $item->max_order_qty = (float) $item->max_order_qty > 0 ? (float) $item->max_order_qty : 0.0;
            $item->step_order_qty = (float) $item->step_order_qty > 0 ? (float) $item->step_order_qty : 1.0;
            $item->sale_price = (float) $item->sale_price;
            $item->discount_price = (float) $item->discount_price;
            $item->has_discount = (int) $item->discount_active === 1 && $item->discount_price > 0;
            $item->unit_price = $item->has_discount ? $item->discount_price : $item->sale_price;
            $item->line_total = $this->money($item->unit_price * $item->quantity);
            $subtotal += $item->line_total;
        }

        $shipmentFee = $shipment ? (float) $shipment->fee : 0.0;
        $paymentFee = $payment ? (float) $payment->fee : 0.0;
        $subtotal = $this->money($subtotal);

        return [
            'items' => $items,
            'shipments' => $shipments,
            'payments' => $payments,
            'shipment' => $shipment,
            'payment' => $payment,
            'shipment_fallback' => $shipmentFallback,
            'payment_fallback' => $paymentFallback,
            'subtotal' => $subtotal,
            'shipment_fee' => $shipmentFee,
            'payment_fee' => $paymentFee,
            'total' => $this->money($subtotal + $shipmentFee + $paymentFee),
            'currency' => $items[0]->currency ?? 'EUR',
        ];
    }

    public function addItem(int $userId, string $sessionId, int $productId, float $quantity): array
    {
        $this->assertOwner($userId, $sessionId);
        $product = $this->loadProduct($productId);
        $this->validateQuantity($product, $quantity);
        $existing = $this->findCartItem($userId, $sessionId, $productId);
        $now = Factory::getDate()->toSql();
        $gross = $this->currentPrice($product);
        $net = $this->netPrice($gross);

        if ($existing) {
            $quantity += (float) $existing->quantity;
            $this->validateQuantity($product, $quantity);
            $query = $this->db->getQuery(true)
                ->update($this->db->quoteName('#__fdshop_cart'))
                ->set($this->db->quoteName('quantity') . ' = ' . $this->db->quote($quantity))
                ->set($this->db->quoteName('unit_price_net') . ' = ' . $this->db->quote($net))
                ->set($this->db->quoteName('unit_price_gross') . ' = ' . $this->db->quote($gross))
                ->set($this->db->quoteName('currency') . ' = ' . $this->db->quote((string) $product->currency))
                ->set($this->db->quoteName('modified') . ' = ' . $this->db->quote($now))
                ->where($this->db->quoteName('id') . ' = ' . (int) $existing->id)
                ->where($this->ownerWhere($userId, $sessionId));
            $this->db->setQuery($query)->execute();
        } else {
            $row = (object) [
                'user_id' => $userId,
                'session_id' => $userId > 0 ? '' : $sessionId,
                'product_id' => $productId,
                'buyer_group_id' => (int) $product->buyer_group_id,
                'quantity' => $quantity,
                'unit_price_net' => $net,
                'unit_price_gross' => $gross,
                'currency' => (string) $product->currency,
                'created' => $now,
            ];
            $this->db->insertObject('#__fdshop_cart', $row);
        }

        return $this->getCart($userId, $sessionId);
    }

    public function updateQuantity(int $userId, string $sessionId, int $cartId, float $quantity, int $shipmentId = 0, int $paymentId = 0): array
    {
        $this->assertOwner($userId, $sessionId);
        $item = $this->loadOwnedCartItem($userId, $sessionId, $cartId);
        $product = $this->loadProduct((int) $item->product_id);
        $this->validateQuantity($product, $quantity);
        $gross = $this->currentPrice($product);
        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__fdshop_cart'))
            ->set($this->db->quoteName('quantity') . ' = ' . $this->db->quote($quantity))
            ->set($this->db->quoteName('unit_price_net') . ' = ' . $this->db->quote($this->netPrice($gross)))
            ->set($this->db->quoteName('unit_price_gross') . ' = ' . $this->db->quote($gross))
            ->set($this->db->quoteName('currency') . ' = ' . $this->db->quote((string) $product->currency))
            ->set($this->db->quoteName('modified') . ' = ' . $this->db->quote(Factory::getDate()->toSql()))
            ->where($this->db->quoteName('id') . ' = ' . $cartId)
            ->where($this->ownerWhere($userId, $sessionId));
        $this->db->setQuery($query)->execute();

        return $this->getCart($userId, $sessionId, $shipmentId, $paymentId);
    }

    public function removeItem(int $userId, string $sessionId, int $cartId, int $shipmentId = 0, int $paymentId = 0): array
    {
        $this->assertOwner($userId, $sessionId);
        $this->loadOwnedCartItem($userId, $sessionId, $cartId);
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__fdshop_cart'))
            ->where($this->db->quoteName('id') . ' = ' . $cartId)
            ->where($this->ownerWhere($userId, $sessionId));
        $this->db->setQuery($query)->execute();

        return $this->getCart($userId, $sessionId, $shipmentId, $paymentId);
    }

    public function validateShipment(int $shipmentId): int
    {
        return $this->validateChoice('shipments', $shipmentId, 'Versandart');
    }

    public function validatePayment(int $paymentId): int
    {
        return $this->validateChoice('payment_methods', $paymentId, 'Zahlungsart');
    }

    private function loadItems(int $userId, string $sessionId): array
    {
        $currentPrice = 'CASE WHEN ' . $this->db->quoteName('p.discount_active') . ' = 1 AND '
            . $this->db->quoteName('p.discount_price') . ' > 0 THEN ' . $this->db->quoteName('p.discount_price')
            . ' ELSE ' . $this->db->quoteName('p.sale_price') . ' END';
        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('c.id'), $this->db->quoteName('c.product_id'), $this->db->quoteName('c.quantity'),
                $this->db->quoteName('p.product_name'), $this->db->quoteName('p.alias'), $this->db->quoteName('p.sale_price'),
                $this->db->quoteName('p.discount_price'), $this->db->quoteName('p.discount_active'), $this->db->quoteName('p.currency'),
                $this->db->quoteName('p.min_order_qty'), $this->db->quoteName('p.max_order_qty'), $this->db->quoteName('p.step_order_qty'),
                $this->db->quoteName('d.sku'), $currentPrice . ' AS ' . $this->db->quoteName('current_price'),
                '(SELECT MIN(' . $this->db->quoteName('pcm.category_id') . ') FROM ' . $this->db->quoteName('#__fdshop_product_category_map', 'pcm')
                    . ' WHERE ' . $this->db->quoteName('pcm.product_id') . ' = ' . $this->db->quoteName('p.id') . ') AS ' . $this->db->quoteName('category_id'),
            ])
            ->from($this->db->quoteName('#__fdshop_cart', 'c'))
            ->innerJoin($this->db->quoteName('#__fdshop_products', 'p') . ' ON ' . $this->db->quoteName('p.id') . ' = ' . $this->db->quoteName('c.product_id'))
            ->innerJoin($this->db->quoteName('#__fdshop_products_details', 'd') . ' ON ' . $this->db->quoteName('d.product_id') . ' = ' . $this->db->quoteName('p.id'))
            ->where($this->ownerWhere($userId, $sessionId, 'c'))
            ->where($this->db->quoteName('p.is_active') . ' = 1')
            ->where($this->db->quoteName('p.is_deleted') . ' = 0')
            ->order($this->db->quoteName('c.created') . ' ASC, ' . $this->db->quoteName('c.id') . ' ASC');
        $this->db->setQuery($query);

        return $this->db->loadObjectList();
    }

    private function attachImages(array $items): void
    {
        $ids = array_values(array_unique(array_map(static fn ($item): int => (int) $item->product_id, $items)));
        if ($ids === []) {
            return;
        }
        $query = $this->db->getQuery(true)
            ->select([$this->db->quoteName('product_id'), $this->db->quoteName('path_standard'), $this->db->quoteName('path_small'), $this->db->quoteName('path_mobile')])
            ->from($this->db->quoteName('#__fdshop_media'))
            ->whereIn($this->db->quoteName('product_id'), $ids)
            ->where($this->db->quoteName('media_type') . ' = ' . $this->db->quote('image'))
            ->order($this->db->quoteName('product_id') . ' ASC, ' . $this->db->quoteName('is_primary') . ' DESC, ' . $this->db->quoteName('ordering') . ' ASC, ' . $this->db->quoteName('id') . ' ASC');
        $this->db->setQuery($query);
        $images = [];
        foreach ($this->db->loadObjectList() as $medium) {
            $id = (int) $medium->product_id;
            if (!isset($images[$id])) {
                $images[$id] = trim((string) ($medium->path_small ?: $medium->path_standard ?: $medium->path_mobile));
            }
        }
        foreach ($items as $item) {
            $item->image = $images[(int) $item->product_id] ?? '';
        }
    }

    private function loadProduct(int $productId): object
    {
        $now = Factory::getDate()->toSql();
        $query = $this->db->getQuery(true)
            ->select([$this->db->quoteName('p.id'), $this->db->quoteName('p.buyer_group_id'), $this->db->quoteName('p.sale_price'), $this->db->quoteName('p.discount_price'), $this->db->quoteName('p.discount_active'), $this->db->quoteName('p.currency'), $this->db->quoteName('p.min_order_qty'), $this->db->quoteName('p.max_order_qty'), $this->db->quoteName('p.step_order_qty'), $this->db->quoteName('d.stock_quantity'), $this->db->quoteName('d.reserved_quantity')])
            ->from($this->db->quoteName('#__fdshop_products', 'p'))
            ->innerJoin($this->db->quoteName('#__fdshop_products_details', 'd') . ' ON ' . $this->db->quoteName('d.product_id') . ' = ' . $this->db->quoteName('p.id'))
            ->where($this->db->quoteName('p.id') . ' = :productId')
            ->where($this->db->quoteName('p.is_active') . ' = 1')
            ->where($this->db->quoteName('p.is_deleted') . ' = 0')
            ->where('(' . $this->db->quoteName('p.publish_up') . ' IS NULL OR ' . $this->db->quoteName('p.publish_up') . ' <= :publishUp)')
            ->where('(' . $this->db->quoteName('p.publish_down') . ' IS NULL OR ' . $this->db->quoteName('p.publish_down') . ' >= :publishDown)')
            ->bind(':productId', $productId, ParameterType::INTEGER)
            ->bind(':publishUp', $now)
            ->bind(':publishDown', $now);
        $this->db->setQuery($query);
        $product = $this->db->loadObject();
        if (!$product) {
            throw new \DomainException('Das Produkt ist nicht verfügbar.');
        }

        return $product;
    }

    private function validateQuantity(object $product, float $quantity): void
    {
        $quantityUnits = (int) round($quantity * 1000);
        $min = max(1000, (int) round((float) $product->min_order_qty * 1000));
        $max = (int) round((float) $product->max_order_qty * 1000);
        $step = max(1, (int) round((float) $product->step_order_qty * 1000));
        if ($quantityUnits < $min) {
            throw new \DomainException('Die Mindestbestellmenge beträgt ' . $this->formatQuantity($min / 1000) . '.');
        }
        if ($max > 0 && $quantityUnits > $max) {
            throw new \DomainException('Die Höchstbestellmenge beträgt ' . $this->formatQuantity($max / 1000) . '.');
        }
        if (($quantityUnits - $min) % $step !== 0) {
            throw new \DomainException('Die Menge muss der Bestellschrittweite ' . $this->formatQuantity($step / 1000) . ' entsprechen.');
        }
        $available = max(0, (float) $product->stock_quantity - (float) $product->reserved_quantity);
        if ($quantity > $available + 0.0001) {
            throw new \DomainException('Gewünschte Menge nicht verfügbar.');
        }
    }

    private function loadOwnedCartItem(int $userId, string $sessionId, int $cartId): object
    {
        $query = $this->db->getQuery(true)->select(['id', 'product_id', 'quantity'])
            ->from($this->db->quoteName('#__fdshop_cart'))
            ->where($this->db->quoteName('id') . ' = ' . $cartId)
            ->where($this->ownerWhere($userId, $sessionId));
        $this->db->setQuery($query);
        $item = $this->db->loadObject();
        if (!$item) {
            throw new \DomainException('Die Warenkorbposition wurde nicht gefunden.');
        }

        return $item;
    }

    private function findCartItem(int $userId, string $sessionId, int $productId): ?object
    {
        $query = $this->db->getQuery(true)->select(['id', 'quantity'])
            ->from($this->db->quoteName('#__fdshop_cart'))
            ->where($this->ownerWhere($userId, $sessionId))
            ->where($this->db->quoteName('product_id') . ' = ' . $productId);
        $this->db->setQuery($query);
        return $this->db->loadObject() ?: null;
    }

    private function loadChoices(string $table): array
    {
        $shipment = $table === 'shipments';
        $name = $shipment ? 'shipment_name' : 'payment_name';
        $fee = $shipment ? 'shipment_price' : 'payment_fee';
        $query = $this->db->getQuery(true)->select([$this->db->quoteName('id'), $this->db->quoteName($name, 'name'), $this->db->quoteName($fee, 'fee'), $this->db->quoteName('is_default')])
            ->from($this->db->quoteName('#__fdshop_' . $table))
            ->where($this->db->quoteName('published') . ' = 1')
            ->order($this->db->quoteName('ordering') . ' ASC, ' . $this->db->quoteName('id') . ' ASC');
        $this->db->setQuery($query);
        return $this->db->loadObjectList();
    }

    private function resolveChoice(array $choices, int $requestedId, string $label): array
    {
        if ($choices === []) {
            return [null, false];
        }
        if ($requestedId > 0) {
            foreach ($choices as $choice) {
                if ((int) $choice->id === $requestedId) {
                    return [$choice, false];
                }
            }
        }
        $defaults = array_values(array_filter($choices, static fn ($choice): bool => (int) $choice->is_default === 1));
        if (count($defaults) > 1) {
            throw new \RuntimeException('Mehrere aktive Datensätze sind als Standard-' . $label . ' markiert.');
        }
        return [$defaults[0] ?? $choices[0], $defaults === []];
    }

    private function validateChoice(string $table, int $id, string $label): int
    {
        foreach ($this->loadChoices($table) as $choice) {
            if ((int) $choice->id === $id) {
                return $id;
            }
        }
        throw new \DomainException('Die gewählte ' . $label . ' ist nicht verfügbar.');
    }

    private function currentPrice(object $product): float
    {
        return $this->money((int) $product->discount_active === 1 && (float) $product->discount_price > 0 ? (float) $product->discount_price : (float) $product->sale_price);
    }

    private function netPrice(float $gross): float
    {
        $query = $this->db->getQuery(true)->select($this->db->quoteName('general_vat_rate'))->from($this->db->quoteName('#__fdshop_config'))->where($this->db->quoteName('id') . ' = 1');
        $this->db->setQuery($query);
        $rate = (float) $this->db->loadResult();
        return round($gross / (1 + ($rate / 100)), 4);
    }

    private function assertOwner(int $userId, string $sessionId): void
    {
        if ($userId < 1 && $sessionId === '') {
            throw new \DomainException('Die Warenkorbsitzung ist nicht verfügbar.');
        }
    }

    private function ownerWhere(int $userId, string $sessionId, string $alias = ''): string
    {
        $prefix = $alias !== '' ? $alias . '.' : '';
        if ($userId > 0) {
            return $this->db->quoteName($prefix . 'user_id') . ' = ' . $userId;
        }

        return $this->db->quoteName($prefix . 'user_id') . ' = 0 AND '
            . $this->db->quoteName($prefix . 'session_id') . ' = ' . $this->db->quote($sessionId);
    }

    private function money(float $value): float
    {
        return round($value, 2);
    }

    private function formatQuantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }
}
