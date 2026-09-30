<?php
namespace FDShop\Component\FDShop\Site\Service;
defined('_JEXEC') or die;

final class CredentialStore
{
    public function __construct(private readonly CredentialResolver $resolver, private readonly ?\Closure $replace = null) {}

    public function status(): array
    {
        $path = $this->resolver->secretFilePath();
        $directory = dirname($path);
        $exists = $path !== '' && is_file($path);
        $parent = dirname($directory);
        $writable = $path !== '' && (($exists && is_readable($path) && is_writable($path))
            || (!$exists && ((is_dir($directory) && is_writable($directory)) || (!is_dir($directory) && is_dir($parent) && is_writable($parent)))));

        return ['path' => $path, 'exists' => $exists, 'readable' => $exists && is_readable($path), 'writable' => $writable];
    }

    public function update(array $changes): void
    {
        $status = $this->status();
        if (!$status['writable']) {
            throw new \RuntimeException('Der externe FDShop-Secret-Store kann von Joomla nicht beschrieben werden.');
        }

        $path = $status['path'];
        $directory = dirname($path);
        if (!is_dir($directory) && (!@mkdir($directory, 0700, true) || !is_dir($directory))) {
            throw new \RuntimeException('Das Verzeichnis für den externen FDShop-Secret-Store kann nicht erstellt werden.');
        }
        $lock = @fopen($path . '.lock', 'c');
        if ($lock === false || !@flock($lock, LOCK_EX)) {
            if (is_resource($lock)) fclose($lock);
            throw new \RuntimeException('Der externe FDShop-Secret-Store ist derzeit gesperrt.');
        }
        @chmod($path . '.lock', 0600);

        try {
            $fresh = new CredentialResolver($path);
            $data = $fresh->fileData();
            if ($status['exists'] && $data === []) {
                throw new \RuntimeException('Der externe FDShop-Secret-Store ist ungültig oder nicht lesbar.');
            }
            $data = $this->normalise($data);
            $mode = strtolower(trim((string) ($changes['mode'] ?? $data['paypal']['mode'])));
            if (!in_array($mode, ['sandbox', 'live'], true)) {
                throw new \RuntimeException('Ungültiger PayPal-Betriebsmodus.');
            }
            if ($this->resolver->modeSource() === 'environment' && $mode !== $this->resolver->paypalMode()) {
                throw new \RuntimeException('Der PayPal-Betriebsmodus wird durch die Serverumgebung vorgegeben.');
            }
            $data['paypal']['mode'] = $mode;

            foreach (['sandbox', 'live'] as $environment) {
                foreach (['client_id', 'webhook_id'] as $name) {
                    if ($this->resolver->paypalSource($environment, $name) === 'environment') continue;
                    if (array_key_exists($environment . '_' . $name, $changes)) {
                        $data['paypal'][$environment][$name] = trim((string) $changes[$environment . '_' . $name]);
                    }
                }
                $secretKey = $environment . '_client_secret';
                if ($this->resolver->paypalSource($environment, 'client_secret') !== 'environment'
                    && trim((string) ($changes[$secretKey] ?? '')) !== '') {
                    $data['paypal'][$environment]['client_secret'] = trim((string) $changes[$secretKey]);
                }
            }

            if ($mode === 'live') {
                foreach (['client_id', 'client_secret', 'webhook_id'] as $name) {
                    $effective = $this->resolver->paypalSource('live', $name) === 'environment'
                        ? $this->resolver->paypalForMode('live', $name)
                        : $data['paypal']['live'][$name];
                    if (trim($effective) === '') {
                        throw new \RuntimeException('Live kann erst mit vollständiger Client-ID, Client Secret und Webhook-ID aktiviert werden.');
                    }
                }
            }

            $this->atomicWrite($path, "<?php\nreturn " . var_export($data, true) . ";\n");
        } finally {
            @flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function normalise(array $data): array
    {
        $paypal = is_array($data['paypal'] ?? null) ? $data['paypal'] : [];
        $result = ['paypal' => ['mode' => strtolower((string) ($paypal['mode'] ?? 'sandbox')) === 'live' ? 'live' : 'sandbox']];
        foreach (['sandbox', 'live'] as $mode) {
            $source = is_array($paypal[$mode] ?? null) ? $paypal[$mode] : [];
            $result['paypal'][$mode] = [];
            foreach (['client_id', 'client_secret', 'webhook_id'] as $name) {
                $result['paypal'][$mode][$name] = is_string($source[$name] ?? null) ? $source[$name] : '';
            }
        }
        return $result;
    }

    private function atomicWrite(string $path, string $content): void
    {
        $directory = dirname($path);
        $temporary = @tempnam($directory, '.fdshop-secrets-');
        if ($temporary === false) throw new \RuntimeException('Temporäre Secret-Datei konnte nicht erstellt werden.');
        try {
            $handle = @fopen($temporary, 'wb');
            if ($handle === false) throw new \RuntimeException('Temporäre Secret-Datei konnte nicht geöffnet werden.');
            try {
                if (!flock($handle, LOCK_EX) || fwrite($handle, $content) !== strlen($content) || !fflush($handle)) {
                    throw new \RuntimeException('Secret-Datei konnte nicht vollständig geschrieben werden.');
                }
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
            @chmod($temporary, 0600);
            $replaced = $this->replace !== null ? ($this->replace)($temporary, $path) : @rename($temporary, $path);
            if (!$replaced) throw new \RuntimeException('Secret-Datei konnte nicht atomar ersetzt werden.');
            @chmod($path, 0600);
        } finally {
            if (is_file($temporary)) @unlink($temporary);
        }
    }
}
