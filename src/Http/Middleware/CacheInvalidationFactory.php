<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Http\Middleware\MiddlewareFactoryInterface;
use PCF\Addendum\Cache\CacheKeyGenerator;
use PCF\Addendum\Http\MiddlewareOptions;

class CacheInvalidationFactory implements MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): CacheInvalidation
    {
        $key = $options->get('key');
        $useSession = $options->get('session', false);
        $params = $options->get('params', []);
        $generator = new CacheKeyGenerator();
        return new CacheInvalidation($generator, $key, $useSession, $params);
    }
}
