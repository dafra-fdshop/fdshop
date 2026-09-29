<?php
namespace FDShop\Component\FDShop\Site\Service;
defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;

final class PayPalClient implements PayPalClientInterface
{
    public function __construct(private readonly CredentialResolverInterface $credentials) {}

    private function value(string $suffix): string
    {
        return $this->credentials->paypal(strtolower($suffix));
    }

    public function mode(): string { return $this->credentials->paypalMode(); }
    public function clientId(): string { return $this->value('CLIENT_ID'); }
    public function configured(): bool { return $this->clientId() !== '' && $this->value('CLIENT_SECRET') !== ''; }
    private function base(): string { return $this->mode() === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com'; }

    private function token(): string
    {
        if (!$this->configured()) throw new \RuntimeException('PayPal ist noch nicht vollständig konfiguriert.');
        $headers = ['Authorization' => 'Basic ' . base64_encode($this->clientId() . ':' . $this->value('CLIENT_SECRET')), 'Content-Type' => 'application/x-www-form-urlencoded'];
        $response = HttpFactory::getHttp()->post($this->base() . '/v1/oauth2/token', 'grant_type=client_credentials', $headers, 15);
        $data = json_decode((string) $response->body, true);
        if ((int) $response->code !== 200 || empty($data['access_token'])) throw new \RuntimeException('PayPal-Authentifizierung ist fehlgeschlagen.');
        return (string) $data['access_token'];
    }

    private function json(string $method, string $path, ?array $payload, array $headers = []): array
    {
        $headers += ['Authorization' => 'Bearer ' . $this->token(), 'Content-Type' => 'application/json', 'Accept' => 'application/json'];
        $http = HttpFactory::getHttp();
        $response = $method === 'POST'
            ? $http->post($this->base() . $path, $payload === null ? '{}' : json_encode($payload, JSON_THROW_ON_ERROR), $headers, 20)
            : $http->get($this->base() . $path, $headers, 20);
        $data = json_decode((string) $response->body, true);
        if ((int) $response->code < 200 || (int) $response->code >= 300 || !is_array($data)) throw new \RuntimeException('PayPal hat die Anfrage nicht erfolgreich verarbeitet.');
        return $data;
    }

    public function createOrder(string $requestId, int $amountMinor, string $currency): array
    {
        return $this->json('POST', '/v2/checkout/orders', ['intent' => 'CAPTURE', 'purchase_units' => [['amount' => ['currency_code' => $currency, 'value' => number_format($amountMinor / 100, 2, '.', '')]]]], ['PayPal-Request-Id' => $requestId]);
    }

    public function captureOrder(string $providerOrderId, string $requestId): array
    {
        return $this->json('POST', '/v2/checkout/orders/' . rawurlencode($providerOrderId) . '/capture', null, ['PayPal-Request-Id' => $requestId]);
    }

    public function verifyWebhook(array $headers, string $body): bool
    {
        $webhookId = $this->value('WEBHOOK_ID');
        if ($webhookId === '') return false;
        $lower = array_change_key_case($headers, CASE_LOWER);
        $payload = [
            'auth_algo' => (string) ($lower['paypal-auth-algo'] ?? ''), 'cert_url' => (string) ($lower['paypal-cert-url'] ?? ''),
            'transmission_id' => (string) ($lower['paypal-transmission-id'] ?? ''), 'transmission_sig' => (string) ($lower['paypal-transmission-sig'] ?? ''),
            'transmission_time' => (string) ($lower['paypal-transmission-time'] ?? ''), 'webhook_id' => $webhookId,
            'webhook_event' => json_decode($body, true, 512, JSON_THROW_ON_ERROR),
        ];
        foreach (['auth_algo','cert_url','transmission_id','transmission_sig','transmission_time'] as $key) if ($payload[$key] === '') return false;
        $result = $this->json('POST', '/v1/notifications/verify-webhook-signature', $payload);
        return ($result['verification_status'] ?? '') === 'SUCCESS';
    }
}
