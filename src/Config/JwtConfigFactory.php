<?php

declare(strict_types=1);

namespace PCF\Addendum\Config;

use PCF\Addendum\Action\FactoryInterface;

class JwtConfigFactory implements FactoryInterface
{
    public function __construct(private SystemEnvironmentProvider $envProvider)
    {
    }

    public function create(): JwtConfig
    {
        $privateKeyPath = $this->envProvider->get('JWT_PRIVATE_KEY_PATH');
        $publicKeyPath = $this->envProvider->get('JWT_PUBLIC_KEY_PATH');
        $privateKeyPassphrase = $this->envProvider->get('JWT_PRIVATE_KEY_PASSPHRASE', '');

        return new JwtConfig(
            privateKeyPath: $privateKeyPath,
            publicKeyPath: $publicKeyPath,
            privateKeyPassphrase: $privateKeyPassphrase !== '' ? $privateKeyPassphrase : null,
            accessTokenLifetime: (int) $this->envProvider->get('JWT_ACCESS_TOKEN_LIFETIME', '7200'),
            refreshTokenLifetime: (int) $this->envProvider->get('JWT_REFRESH_TOKEN_LIFETIME', '1209600')
        );
    }
}
