<?php

declare(strict_types=1);

namespace CitiesRpg\Tests\Middleware;

use PCF\Addendum\Cache\CacheKeyGenerator;
use PCF\Addendum\Http\Middleware\Cache;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CacheTest extends TestCase
{
    public function testAddsCacheHeaders(): void
    {
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new PsrResponse(200);
            }
        };
        $generator = new CacheKeyGenerator();
        $middleware = new Cache($generator, ttl: 120, key: 'foo');
        $request = new ServerRequest('GET', '/');
        $response = $middleware->process($request, $handler);
        $this->assertSame('foo', $response->getHeaderLine('X-Cache-Key'));
        $this->assertSame('120', $response->getHeaderLine('X-Cache-Ttl'));
    }

    public function testGeneratesKeyWithSessionAndParams(): void
    {
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new PsrResponse(200);
            }
        };
        $generator = new CacheKeyGenerator();
        $middleware = new Cache($generator, params: ['foo'], useSession: true);
        $request = (new ServerRequest('GET', '/path'))
            ->withQueryParams(['foo' => 'bar'])
            ->withAttribute('token_jti', 'abc');
        $response = $middleware->process($request, $handler);
        $expected = md5('/path|foo=bar|session=abc');
        $this->assertSame($expected, $response->getHeaderLine('X-Cache-Key'));
        $this->assertSame('60', $response->getHeaderLine('X-Cache-Ttl'));
    }
}
