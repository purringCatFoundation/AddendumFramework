<?php

declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Auth\TokenValidationRepositoryFactory;
use PCF\Addendum\Auth\ApplicationTokenValidatorFactory;
use PCF\Addendum\Config\JwtConfigFactory;
use PCF\Addendum\Config\SystemEnvironmentProvider;
use PCF\Addendum\Database\DbConnectionFactory;
use PCF\Addendum\Http\MiddlewareOptions;

class AuthFactory implements MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): Auth
    {
        $tokenValidationRepository = new TokenValidationRepositoryFactory(new DbConnectionFactory())->create();
        $jwtConfig = new JwtConfigFactory(new SystemEnvironmentProvider())->create();

        return new Auth(
            $jwtConfig->publicKeyPath,
            $tokenValidationRepository,
            new ApplicationTokenValidatorFactory()->create()
        );
    }
}
