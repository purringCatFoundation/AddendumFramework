<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Middleware;

use Pradzikowski\Framework\Config\JwtConfigFactory;

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
