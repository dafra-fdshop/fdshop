<?php

namespace FDShop\Component\FDShop\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class CartContinuationService implements CartContinuationServiceInterface
{
    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly CartServiceInterface $cart,
        private readonly PaymentServiceInterface $payments
    ) {
    }

    public function ensure(string $guestSessionId, string $intent, array $metadata, ?string $token = null): string
    {
        if ($guestSessionId === '' || !in_array($intent, ['account', 'checkout'], true)) {
            throw new \DomainException('Die Warenkorb-Fortsetzung ist ungültig.');
        }

        $existing = $token !== null ? $this->load($token, false) : null;
        $now = Factory::getDate();
        if ($existing && (string) $existing->guest_session_id === $guestSessionId && (string) $existing->status === 'active'
            && (string) $existing->expires_at > $now->toSql()) {
            $intent = $intent === 'checkout' ? 'checkout' : (string) $existing->intent;
            $query = $this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_cart_continuations'))
                ->set('intent=' . $this->db->quote($intent))
                ->set('shipment_id=' . (int) ($metadata['shipment_id'] ?? 0))
                ->set('payment_id=' . (int) ($metadata['payment_id'] ?? 0))
                ->set('coupon_code=' . $this->db->quote((string) ($metadata['coupon_code'] ?? '')))
                ->where('id=' . (int) $existing->id);
            $this->db->setQuery($query)->execute();
            return $token;
        }

        $raw = bin2hex(random_bytes(32));
        $expires = (clone $now)->modify('+2 hours')->toSql();
        $row = (object) [
            'token_hash' => hash('sha256', $raw),
            'guest_session_id' => $guestSessionId,
            'intent' => $intent,
            'user_id' => 0,
            'shipment_id' => (int) ($metadata['shipment_id'] ?? 0),
            'payment_id' => (int) ($metadata['payment_id'] ?? 0),
            'coupon_code' => (string) ($metadata['coupon_code'] ?? ''),
            'status' => 'active',
            'expires_at' => $expires,
            'created' => $now->toSql(),
        ];
        $this->db->insertObject('#__fdshop_cart_continuations', $row);
        return $raw;
    }

    public function resolveAfterLogin(string $token, int $userId, array $userMetadata): array
    {
        if ($userId < 1) {
            throw new \DomainException('Bitte melden Sie sich an.');
        }
        $continuation = $this->load($token, true);
        if (!$continuation) {
            return ['state' => 'none', 'target' => 'account'];
        }
        if ((string) $continuation->status === 'pending') {
            return (int) $continuation->user_id === $userId
                ? ['state' => 'conflict', 'target' => 'cart']
                : ['state' => 'none', 'target' => 'account'];
        }
        $guestHas = $this->hasContent(0, (string) $continuation->guest_session_id);
        $userHas = $this->hasContent($userId, '');
        if ($guestHas && $userHas) {
            $query = $this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_cart_continuations'))
                ->set('status=' . $this->db->quote('pending'))
                ->set('user_id=' . $userId)
                ->where('id=' . (int) $continuation->id)->where('status=' . $this->db->quote('active'));
            $this->db->setQuery($query)->execute();
            return ['state' => 'conflict', 'target' => 'cart'];
        }
        if ($guestHas) {
            $this->finalize($continuation, $userId, 'guest', $userMetadata);
        } else {
            $this->consume($continuation, $userId);
        }
        return ['state' => 'resolved', 'target' => (string) $continuation->intent];
    }

    public function getConflict(string $token, int $userId, array $userMetadata): ?array
    {
        $continuation = $this->load($token, true);
        if (!$continuation || (string) $continuation->status !== 'pending' || (int) $continuation->user_id !== $userId) {
            return null;
        }
        if (!$this->hasContent(0, (string) $continuation->guest_session_id) || !$this->hasContent($userId, '')) {
            return null;
        }
        return [
            'guest' => $this->summary(0, (string) $continuation->guest_session_id, $this->metadata($continuation)),
            'user' => $this->summary($userId, '', $userMetadata),
            'intent' => (string) $continuation->intent,
        ];
    }

    public function choose(string $token, int $userId, string $choice, array $userMetadata): array
    {
        if (!in_array($choice, ['guest', 'user'], true)) {
            throw new \DomainException('Bitte wählen Sie einen Warenkorb aus.');
        }
        $continuation = $this->load($token, true);
        if (!$continuation || (string) $continuation->status !== 'pending' || (int) $continuation->user_id !== $userId) {
            throw new \DomainException('Die Warenkorbauswahl ist ungültig oder abgelaufen.');
        }
        $query = $this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_cart_continuations'))
            ->set('status=' . $this->db->quote('processing'))->where('id=' . (int) $continuation->id)
            ->where('user_id=' . $userId)->where('status=' . $this->db->quote('pending'));
        $this->db->setQuery($query)->execute();
        if ($this->db->getAffectedRows() !== 1) throw new \DomainException('Die Warenkorbauswahl wird bereits verarbeitet.');
        $continuation->status = 'processing';
        try {
            $this->finalize($continuation, $userId, $choice, $userMetadata);
        } catch (\Throwable $error) {
            $query = $this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_cart_continuations'))
                ->set('status=' . $this->db->quote('pending'))->where('id=' . (int) $continuation->id)
                ->where('user_id=' . $userId)->where('status=' . $this->db->quote('processing'));
            $this->db->setQuery($query)->execute();
            throw $error;
        }
        return ['target' => (string) $continuation->intent];
    }

    private function finalize(object $continuation, int $userId, string $choice, array $userMetadata): void
    {
        if ($choice === 'guest') {
            $this->cart->assertOwnerEligibleForUser($userId, 0, (string) $continuation->guest_session_id);
        }
        if ($choice === 'guest' && (string) $continuation->status === 'processing') {
            $this->payments->abandonActiveForUser($userId);
        }
        $this->db->transactionStart();
        try {
            $locked = $this->lock((int) $continuation->id);
            if (!$locked || !in_array((string) $locked->status, ['active', 'pending', 'processing'], true)
                || ((int) $locked->user_id !== 0 && (int) $locked->user_id !== $userId)) {
                throw new \DomainException('Die Warenkorbauswahl wurde bereits verarbeitet.');
            }
            $guestSession = (string) $locked->guest_session_id;
            if ($choice === 'guest') {
                $this->deleteOwner($userId, '');
                $this->transferOwner($guestSession, $userId);
            } else {
                $this->deleteOwner(0, $guestSession);
            }
            $this->markConsumed($locked, $userId);
            $this->db->transactionCommit();
        } catch (\Throwable $error) {
            $this->db->transactionRollback();
            throw $error;
        }
        $metadata = $choice === 'guest' ? $this->metadata($continuation) : $userMetadata;
        $metadata = $this->validatedMetadata($userId, $metadata);
        $this->writeUserMetadata($userId, $metadata);
    }

    private function transferOwner(string $sessionId, int $userId): void
    {
        foreach (['#__fdshop_cart', '#__fdshop_cart_bundles'] as $table) {
            $query = $this->db->getQuery(true)->update($this->db->quoteName($table))
                ->set('user_id=' . $userId)->set('session_id=' . $this->db->quote(''))
                ->where('user_id=0')->where('session_id=' . $this->db->quote($sessionId));
            $this->db->setQuery($query)->execute();
        }
    }

    private function deleteOwner(int $userId, string $sessionId): void
    {
        $owner = $userId > 0 ? 'user_id=' . $userId : '(user_id=0 AND session_id=' . $this->db->quote($sessionId) . ')';
        $this->db->setQuery('DELETE i FROM ' . $this->db->quoteName('#__fdshop_cart_bundle_items') . ' AS i INNER JOIN '
            . $this->db->quoteName('#__fdshop_cart_bundles') . ' AS b ON b.id=i.cart_bundle_id WHERE ' . $owner)->execute();
        foreach (['#__fdshop_cart_bundles', '#__fdshop_cart'] as $table) {
            $this->db->setQuery('DELETE FROM ' . $this->db->quoteName($table) . ' WHERE ' . $owner)->execute();
        }
    }

    private function summary(int $userId, string $sessionId, array $metadata): array
    {
        try {
            $cart = $this->cart->getCart($userId, $sessionId, (int) ($metadata['shipment_id'] ?? 0), (int) ($metadata['payment_id'] ?? 0), (string) ($metadata['coupon_code'] ?? ''));
        } catch (\DomainException) {
            $cart = $this->cart->getCart($userId, $sessionId, (int) ($metadata['shipment_id'] ?? 0), (int) ($metadata['payment_id'] ?? 0), '');
        }
        $names = [];
        foreach ($cart['items'] as $item) {
            $names[] = (string) $item->sales_name;
        }
        foreach ($cart['bundles'] as $bundle) {
            $names[] = (string) $bundle->bundle_name;
        }
        return [
            'count' => count($cart['items']) + count($cart['bundles']),
            'total' => number_format((float) $cart['total'], 2, ',', '.') . ' ' . (strtoupper((string) $cart['currency']) === 'EUR' ? '€' : strtoupper((string) $cart['currency'])),
            'names' => array_slice($names, 0, 3),
            'remaining' => max(0, count($names) - 3),
        ];
    }

    private function hasContent(int $userId, string $sessionId): bool
    {
        $owner = $userId > 0 ? 'user_id=' . $userId : '(user_id=0 AND session_id=' . $this->db->quote($sessionId) . ')';
        foreach (['#__fdshop_cart', '#__fdshop_cart_bundles'] as $table) {
            $this->db->setQuery('SELECT COUNT(*) FROM ' . $this->db->quoteName($table) . ' WHERE ' . $owner);
            if ((int) $this->db->loadResult() > 0) {
                return true;
            }
        }
        return false;
    }

    private function load(string $token, bool $validOnly): ?object
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $query = $this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_cart_continuations'))
            ->where('token_hash=' . $this->db->quote(hash('sha256', $token)));
        if ($validOnly) {
            $query->where("status IN ('active','pending')")->where('expires_at>' . $this->db->quote(Factory::getDate()->toSql()));
        }
        $this->db->setQuery($query);
        return $this->db->loadObject() ?: null;
    }

    private function lock(int $id): ?object
    {
        $this->db->setQuery('SELECT * FROM ' . $this->db->quoteName('#__fdshop_cart_continuations') . ' WHERE id=' . $id . ' FOR UPDATE');
        return $this->db->loadObject() ?: null;
    }

    private function consume(object $continuation, int $userId): void
    {
        $this->db->transactionStart();
        try {
            $locked = $this->lock((int) $continuation->id);
            if (!$locked || (string) $locked->status !== 'active') {
                throw new \DomainException('Die Warenkorb-Fortsetzung wurde bereits verarbeitet.');
            }
            $this->markConsumed($locked, $userId);
            $this->db->transactionCommit();
        } catch (\Throwable $error) {
            $this->db->transactionRollback();
            throw $error;
        }
    }

    private function markConsumed(object $continuation, int $userId): void
    {
        $query = $this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_cart_continuations'))
            ->set('status=' . $this->db->quote('consumed'))->set('user_id=' . $userId)
            ->set('consumed_at=' . $this->db->quote(Factory::getDate()->toSql()))
            ->where('id=' . (int) $continuation->id)->where("status IN ('active','pending','processing')");
        $this->db->setQuery($query)->execute();
        if ($this->db->getAffectedRows() !== 1) {
            throw new \DomainException('Die Warenkorb-Fortsetzung wurde bereits verarbeitet.');
        }
    }

    private function metadata(object $continuation): array
    {
        return ['shipment_id' => (int) $continuation->shipment_id, 'payment_id' => (int) $continuation->payment_id, 'coupon_code' => (string) $continuation->coupon_code];
    }

    private function writeUserMetadata(int $userId, array $metadata): void
    {
        $session = Factory::getApplication()->getSession();
        foreach (['shipment_id', 'payment_id', 'coupon_code'] as $field) {
            $session->set('com_fdshop.cart.' . $userId . '.' . $field, $metadata[$field] ?? ($field === 'coupon_code' ? '' : 0));
            $session->clear('com_fdshop.cart.0.' . $field);
        }
    }

    private function validatedMetadata(int $userId, array $metadata): array
    {
        try {
            $cart = $this->cart->getCart($userId, '', (int) ($metadata['shipment_id'] ?? 0), (int) ($metadata['payment_id'] ?? 0), (string) ($metadata['coupon_code'] ?? ''));
        } catch (\DomainException) {
            $cart = $this->cart->getCart($userId, '', (int) ($metadata['shipment_id'] ?? 0), (int) ($metadata['payment_id'] ?? 0), '');
        }
        return ['shipment_id' => (int) ($cart['shipment']->id ?? 0), 'payment_id' => (int) ($cart['payment']->id ?? 0), 'coupon_code' => (string) ($cart['coupon_code'] ?? '')];
    }
}
