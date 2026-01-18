<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareRequestHandlerFactory
{
    public function create(MiddlewareInterface $middleware, RequestHandlerInterface $nextHandler): RequestHandlerInterface
    {
        return new MiddlewareRequestHandler($middleware, $nextHandler);
    }
}
