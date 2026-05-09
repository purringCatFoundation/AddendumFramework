<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Middleware;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Http\Middleware\SecurityHeaders;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SecurityHeadersTest extends TestCase
{
    public function testAddsSecurityHeadersAndRemovesServerDisclosureHeaders(): void
    {
        $middleware = new SecurityHeaders();
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, ['X-Powered-By' => 'PHP', 'Server' => 'example']);
            }
        };

        $response = $middleware->process(new ServerRequest('GET', '/'), $handler);

        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        self::assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        self::assertSame('1; mode=block', $response->getHeaderLine('X-XSS-Protection'));
        self::assertSame("default-src 'none'; frame-ancestors 'none'", $response->getHeaderLine('Content-Security-Policy'));
        self::assertSame('strict-origin-when-cross-origin', $response->getHeaderLine('Referrer-Policy'));
        self::assertSame('', $response->getHeaderLine('X-Powered-By'));
        self::assertSame('', $response->getHeaderLine('Server'));
    }
}
