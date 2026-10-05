<?php

declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Auth\TokenValidationRepositoryFactory;
use PCF\Addendum\Config\JwtConfigFactory;
use PCF\Addendum\Config\SystemEnvironmentProvider;
use PCF\Addendum\Database\DbConnectionFactory;
use PCF\Addendum\Http\MiddlewareOptions;

final class RefreshAuthFactory implements MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): RefreshAuth
    {
        $config = new JwtConfigFactory(new SystemEnvironmentProvider())->create();

        return new RefreshAuth(
            $config->publicKeyPath,
            new TokenValidationRepositoryFactory(new DbConnectionFactory())->create()
        );
    }
}
