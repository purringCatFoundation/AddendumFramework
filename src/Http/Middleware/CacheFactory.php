<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Http\Middleware\MiddlewareFactoryInterface;
use PCF\Addendum\Cache\CacheKeyGenerator;
use PCF\Addendum\Http\MiddlewareOptions;

class CacheFactory implements MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): Cache
    {
        $ttl = $options->get('ttl', 60);
        $key = $options->get('key');
        $useSession = $options->get('session', false);
        $params = $options->get('params', []);
        $generator = new CacheKeyGenerator();
        return new Cache($generator, $ttl, $key, $useSession, $params);
    }
}
