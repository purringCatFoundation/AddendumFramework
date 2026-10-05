<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Cache\RedisCacheFactory;
use PCF\Addendum\Config\SystemEnvironmentProvider;
use PCF\Addendum\Http\MiddlewareOptions;

class RequestSignatureFactory implements MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): RequestSignature
    {
        $environmentProvider = new SystemEnvironmentProvider();

        return new RequestSignature(
            requestSignatureSecret: $environmentProvider->get('REQUEST_SIGNATURE_SECRET'),
            replayCache: new PsrRequestReplayCache(new RedisCacheFactory()->create())
        );
    }
}
