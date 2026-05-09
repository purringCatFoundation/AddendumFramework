<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use Ds\Vector;
use PCF\Addendum\Cache\CacheKeyGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CacheInvalidation implements MiddlewareInterface
{
    /** @var Vector<string> */
    private Vector $params;

    /**
     * @param iterable<string> $params
     */
    public function __construct(
        private CacheKeyGenerator $generator,
        private ?string $key = null,
        private bool $useSession = false,
        iterable $params = []
    ) {
        $this->params = $params instanceof Vector ? $params->copy() : new Vector($params);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $key = $this->key ?? $this->generator->generate($request, $this->useSession, $this->params);
        return $response->withHeader('X-Cache-Invalidate', $key);
    }
}
