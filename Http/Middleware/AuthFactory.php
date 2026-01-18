<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Middleware;

use Pradzikowski\Framework\Http\Middleware\MiddlewareFactoryInterface;
use Pradzikowski\Framework\Auth\TokenValidationRepositoryFactory;
use Pradzikowski\Framework\Http\MiddlewareOptions;
use Pradzikowski\Framework\Repository\User\ApplicationTokenRepositoryFactory;
use RuntimeException;

class AuthFactory implements MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): Auth
    {
        $tokenValidationRepository = new TokenValidationRepositoryFactory()->create();
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