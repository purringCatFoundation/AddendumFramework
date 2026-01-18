<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Dummy implements MiddlewareInterface
{
    public function __construct(private array $options = [])
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (isset($this->options['attr'])) {
            $request = $request->withAttribute('dummy', $this->options['attr']);
        }
        $response = $handler->handle($request);
        if (isset($this->options['header'])) {
            return $response->withHeader('X-Dummy', (string) $this->options['header']);
        }
        return $response;
    }
}
