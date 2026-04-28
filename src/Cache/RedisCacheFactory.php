<?php
declare(strict_types=1);

namespace PCF\Addendum\Cache;

use Predis\Client;

class RedisCacheFactory
{
    public function create(): RedisCache
    {
        $url = $this->getEnvVar('REDIS_URL');

        if ($url !== null) {
            return new RedisCache(new Client($url));
        }

        $parameters = [
            'host' => $this->getEnvVar('REDIS_HOST', '127.0.0.1'),
            'port' => (int) $this->getEnvVar('REDIS_PORT', '6379'),
        ];

        $password = $this->getEnvVar('REDIS_PASSWORD');
        if ($password !== null) {
            $parameters['password'] = $password;
        }

        $client = new Client($parameters);
        return new RedisCache($client);
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
