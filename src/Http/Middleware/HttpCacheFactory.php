<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use InvalidArgumentException;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\Cache\HttpCacheRuntime;
use PCF\Addendum\Http\Cache\ResourcePolicyCollection;

final class HttpCacheFactory implements MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): HttpCache
    {
        $policies = $options->get('resourcePolicies');
        $runtime = $options->get('httpCacheRuntime');

        if (!$policies instanceof ResourcePolicyCollection) {
            throw new InvalidArgumentException('HTTP cache resource policies are required');
        }

        if (!$runtime instanceof HttpCacheRuntime) {
            throw new InvalidArgumentException('HTTP cache runtime is required');
        }

        return new HttpCache($policies, $runtime);
    }
}
