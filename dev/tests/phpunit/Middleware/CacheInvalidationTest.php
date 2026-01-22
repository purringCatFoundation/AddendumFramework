<?php

declare(strict_types=1);

namespace CitiesRpg\Tests\Middleware;

use PCF\Addendum\Cache\CacheKeyGenerator;
use PCF\Addendum\Http\Middleware\CacheInvalidation;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CacheInvalidationTest extends TestCase
{
    public function testAddsInvalidationHeader(): void
    {
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new PsrResponse(200);
            }
        };
        $generator = new CacheKeyGenerator();
        $middleware = new CacheInvalidation($generator, params: ['foo']);
        $request = (new ServerRequest('POST', '/path'))
            ->withQueryParams(['foo' => 'bar'])
            ->withAttribute('token_jti', 'abc');
        $response = $middleware->process($request, $handler);
        $expected = md5('/path|foo=bar');
        $this->assertSame($expected, $response->getHeaderLine('X-Cache-Invalidate'));
    }
}
