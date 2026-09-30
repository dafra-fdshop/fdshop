<?php
namespace FDShop\Component\FDShop\Site\Service;
defined('_JEXEC') or die;

final class CredentialResolver implements CredentialResolverInterface
{
    private ?array $secrets = null;

    public function __construct(private readonly ?string $secretFile = null) {}

    public function paypalMode(): string
    {
        $environment = strtolower(trim((string) getenv('FDSHOP_PAYPAL_MODE')));
        if (in_array($environment, ['sandbox', 'live'], true)) {
            return $environment;
        }

        $fileMode = strtolower((string) ($this->load()['paypal']['mode'] ?? ''));
        return $fileMode === 'live' ? 'live' : 'sandbox';
    }

    public function paypal(string $name): string
    {
        if (!in_array($name, ['client_id', 'client_secret', 'webhook_id'], true)) {
            return '';
        }

        $environmentName = 'FDSHOP_PAYPAL_' . strtoupper($this->paypalMode()) . '_' . strtoupper($name);
        $environment = trim((string) getenv($environmentName));
        if ($environment !== '') {
            return $environment;
        }

        return trim((string) ($this->load()['paypal'][$this->paypalMode()][$name] ?? ''));
    }

    public function paypalConfigured(string $name): bool
    {
        return $this->paypal($name) !== '';
    }

    public function paypalSource(string $mode, string $name): string
    {
        if (!in_array($mode, ['sandbox', 'live'], true) || !in_array($name, ['client_id', 'client_secret', 'webhook_id'], true)) {
            return 'none';
        }

        $environmentName = 'FDSHOP_PAYPAL_' . strtoupper($mode) . '_' . strtoupper($name);
        if (trim((string) getenv($environmentName)) !== '') {
            return 'environment';
        }

        return trim((string) ($this->load()['paypal'][$mode][$name] ?? '')) !== '' ? 'file' : 'none';
    }

    public function paypalForMode(string $mode, string $name): string
    {
        if (!in_array($mode, ['sandbox', 'live'], true) || !in_array($name, ['client_id', 'client_secret', 'webhook_id'], true)) {
            return '';
        }
        $environment = trim((string) getenv('FDSHOP_PAYPAL_' . strtoupper($mode) . '_' . strtoupper($name)));
        return $environment !== '' ? $environment : trim((string) ($this->load()['paypal'][$mode][$name] ?? ''));
    }

    public function modeSource(): string
    {
        return in_array(strtolower(trim((string) getenv('FDSHOP_PAYPAL_MODE'))), ['sandbox', 'live'], true)
            ? 'environment'
            : 'file';
    }

    public function secretFilePath(): string
    {
        return $this->path();
    }

    public function fileData(): array
    {
        return $this->load();
    }

    private function path(): string
    {
        if ($this->secretFile !== null) {
            return trim($this->secretFile);
        }

        $configured = trim((string) getenv('FDSHOP_SECRET_FILE'));
        if ($configured !== '') {
            return $configured;
        }

        return defined('JPATH_ROOT')
            ? dirname(rtrim((string) JPATH_ROOT, '/\\')) . '/fdshop-data/secrets/fdshop-secrets.php'
            : '';
    }

    private function load(): array
    {
        if ($this->secrets !== null) {
            return $this->secrets;
        }

        $this->secrets = [];
        $path = $this->path();
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            return $this->secrets;
        }

        $bufferLevel = ob_get_level();
        ob_start();
        set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $data = (static fn (string $file): mixed => include $file)($path);
        } catch (\Throwable) {
            $data = null;
        } finally {
            restore_error_handler();
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
        }

        if ($this->valid($data)) {
            $this->secrets = $data;
        }

        return $this->secrets;
    }

    private function valid(mixed $data): bool
    {
        if (!is_array($data) || array_diff(array_keys($data), ['paypal']) !== [] || !is_array($data['paypal'] ?? null)) {
            return false;
        }

        $paypal = $data['paypal'];
        if (array_diff(array_keys($paypal), ['mode', 'sandbox', 'live']) !== []) {
            return false;
        }
        if (isset($paypal['mode']) && (!is_string($paypal['mode']) || !in_array(strtolower($paypal['mode']), ['sandbox', 'live'], true))) {
            return false;
        }

        foreach (['sandbox', 'live'] as $mode) {
            if (!isset($paypal[$mode])) {
                continue;
            }
            if (!is_array($paypal[$mode]) || array_diff(array_keys($paypal[$mode]), ['client_id', 'client_secret', 'webhook_id']) !== []) {
                return false;
            }
            foreach ($paypal[$mode] as $value) {
                if (!is_string($value)) {
                    return false;
                }
            }
        }

        return true;
    }
}
