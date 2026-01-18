<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareRequestHandler implements RequestHandlerInterface
{
    public function __construct(private MiddlewareInterface $middleware, private RequestHandlerInterface $nextHandler)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->middleware->process($request, $this->nextHandler);
    }
}
