<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Http\Middleware\MiddlewareFactoryInterface;
use PCF\Addendum\Auth\TokenValidationRepositoryFactory;
use PCF\Addendum\Database\DbConnectionFactory;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Repository\User\ApplicationTokenRepositoryFactory;
use RuntimeException;

class AuthFactory implements MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): Auth
    {
        $tokenValidationRepository = new TokenValidationRepositoryFactory(new DbConnectionFactory())->create();
        $applicationTokenRepository = new ApplicationTokenRepositoryFactory()->create();

        return new Auth(
            $this->getEnvVar('JWT_SECRET'),
            $tokenValidationRepository,
            $applicationTokenRepository
        );
    }

    private function getEnvVar(string $name, ?string $default = null): string
    {
        $value = getenv($name);
        if ($value === false) {
            if ($default === null) {
                throw new RuntimeException("Environment variable {$name} is required but not set");
            }
            return $default;
        }
        return $value;
    }
}
