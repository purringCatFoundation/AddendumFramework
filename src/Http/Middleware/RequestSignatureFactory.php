<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Cache\RedisCacheFactory;
use PCF\Addendum\Config\JwtConfigFactory;
use PCF\Addendum\Http\MiddlewareOptions;

class RequestSignatureFactory implements MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): RequestSignature
    {
        $jwtConfig = (new JwtConfigFactory())->create();

        return new RequestSignature(
            jwtSecret: $jwtConfig->secret,
            replayCache: (new RedisCacheFactory())->create()
        );
    }
}
