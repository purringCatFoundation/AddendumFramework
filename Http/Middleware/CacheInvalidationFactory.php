<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Middleware;

use Pradzikowski\Framework\Http\Middleware\MiddlewareFactoryInterface;
use Pradzikowski\Framework\Cache\CacheKeyGenerator;
use Pradzikowski\Framework\Http\MiddlewareOptions;

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
