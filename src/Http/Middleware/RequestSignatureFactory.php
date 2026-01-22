<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Config\JwtConfigFactory;

class RequestSignatureFactory
{
    public function create(): RequestSignature
    {
        $jwtConfig = (new JwtConfigFactory())->create();

        return new RequestSignature(
            jwtSecret: $jwtConfig->secret
        );
    }
}
