<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Http\MiddlewareOptions;
use Predis\Client as RedisClient;

class RateLimitMiddlewareFactory implements MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): RateLimitMiddleware
    {
        return new RateLimitMiddleware($this->createRedisClient());
    }

    private function createRedisClient(): RedisClient
    {
        $redisUrl = $this->getEnvVar('REDIS_URL');

        if ($redisUrl !== null) {
            return new RedisClient($redisUrl);
        }

        $parameters = [
            'host' => $this->getEnvVar('REDIS_HOST', '127.0.0.1'),
            'port' => (int) $this->getEnvVar('REDIS_PORT', '6379'),
        ];

        $password = $this->getEnvVar('REDIS_PASSWORD');
        if ($password !== null) {
            $parameters['password'] = $password;
        }

        return new RedisClient($parameters);
    }

    private function getEnvVar(string $name, ?string $default = null): ?string
    {
        $value = $_ENV[$name] ?? getenv($name);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }
}
