<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use Ds\Map;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Dummy implements MiddlewareInterface
{
    /** @var Map<string, mixed> */
    private Map $options;

    public function __construct(iterable $options = [])
    {
        $this->options = $options instanceof Map ? $options->copy() : new Map($options);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->options->hasKey('attr')) {
            $request = $request->withAttribute('dummy', $this->options->get('attr'));
        }
        $response = $handler->handle($request);
        if ($this->options->hasKey('header')) {
            return $response->withHeader('X-Dummy', (string) $this->options->get('header'));
        }
        return $response;
    }
}
