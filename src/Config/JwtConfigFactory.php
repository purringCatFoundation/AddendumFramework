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
        return new JwtConfig(
            $this->envProvider->get('JWT_SECRET'),
            (int) $this->envProvider->get('JWT_ACCESS_TOKEN_LIFETIME', '7200'),
            (int) $this->envProvider->get('JWT_REFRESH_TOKEN_LIFETIME', '1209600')
        );
    }
}
