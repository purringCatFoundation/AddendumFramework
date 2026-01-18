<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Middleware;

use Pradzikowski\Framework\Cache\CacheKeyGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Cache implements MiddlewareInterface
{
    public function __construct(
        private CacheKeyGenerator $generator,
        private int $ttl = 60,
        private ?string $key = null,
        private bool $useSession = false,
        private array $params = []
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $cacheKey = $this->key ?? $this->generator->generate($request, $this->useSession, $this->params);
        return $response
            ->withHeader('X-Cache-Key', $cacheKey)
            ->withHeader('X-Cache-Ttl', (string) $this->ttl);
    }
}
