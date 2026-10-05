<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Support;

use RuntimeException;

final class JwtKeyPair
{
    private static ?self $shared = null;

    public function __construct(
        public readonly string $privateKeyPath,
        public readonly string $publicKeyPath,
        public readonly ?string $privateKeyPassphrase = null
    ) {
    }

    public static function shared(): self
    {
        return self::$shared ??= self::create();
    }

    public static function create(?string $privateKeyPassphrase = null): self
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($key === false) {
            throw new RuntimeException('Failed to generate RSA private key for tests');
        }

        if (!openssl_pkey_export($key, $privateKey, $privateKeyPassphrase)) {
            throw new RuntimeException('Failed to export RSA private key for tests');
        }

        $details = openssl_pkey_get_details($key);
        if (!is_array($details) || !isset($details['key'])) {
            throw new RuntimeException('Failed to export RSA public key for tests');
        }

        $directory = sys_get_temp_dir() . '/addendum-jwt-test-' . bin2hex(random_bytes(8));
        if (!mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Failed to create JWT key directory for tests');
        }

        $privateKeyPath = $directory . '/jwt_private.pem';
        $publicKeyPath = $directory . '/jwt_public.pem';

        file_put_contents($privateKeyPath, $privateKey);
        file_put_contents($publicKeyPath, $details['key']);
        chmod($privateKeyPath, 0600);
        chmod($publicKeyPath, 0644);

        register_shutdown_function(static function () use ($directory): void {
            foreach (scandir($directory) ?: [] as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                @unlink($directory . '/' . $file);
            }

            @rmdir($directory);
        });

        return new self($privateKeyPath, $publicKeyPath, $privateKeyPassphrase);
    }
}
