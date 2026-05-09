<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Middleware;

use PCF\Addendum\Http\Middleware\RateLimitMiddleware;
use PCF\Addendum\Http\Middleware\RedisInterface;
use PCF\Addendum\Http\RouteParameters;
use PCF\Addendum\Attribute\RateLimit;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

final class RateLimitMiddlewareTest extends TestCase
{
    private RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(RequestHandlerInterface::class);
    }

    private function createRedisMock(): RedisInterface
    {
        return new class implements RedisInterface {
            private int $cardValue = 0;
            private ?string $lastKey = null;
            private int $zaddCalls = 0;

            public function setCardValue(int $value): void
            {
                $this->cardValue = $value;
            }

            public function getLastKey(): ?string
            {
                return $this->lastKey;
            }

            public function getZaddCalls(): int
            {
                return $this->zaddCalls;
            }

            public function zremrangebyscore(string $key, string $min, string $max): int
            {
                $this->lastKey = $key;

                return 0;
            }

            public function zcard(string $key): int
            {
                $this->lastKey = $key;

                return $this->cardValue;
            }

            public function zadd(string $key, array $values): int
            {
                $this->lastKey = $key;
                $this->zaddCalls++;

                return 1;
            }

            public function expire(string $key, int $seconds): bool
            {
                $this->lastKey = $key;

                return true;
            }
        };
    }

    public function testPassesThroughWithoutActionClass(): void
    {
        $redis = $this->createRedisMock();
        $middleware = new RateLimitMiddleware($redis);

        $request = new ServerRequest('GET', '/test');
        $expectedResponse = new Response(200);

        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $response = $middleware->process($request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testReturns429WhenRateLimitExceeded(): void
    {
        $redis = $this->createRedisMock();
        $redis->setCardValue(100); // At limit
        $middleware = new RateLimitMiddleware($redis);

        $request = new ServerRequest('GET', '/test');
        $request = $request
            ->withAttribute('action_class', TestRateLimitedAction::class)
            ->withAttribute('user_uuid', 'user-uuid-123');

        $response = $middleware->process($request, $this->handler);

        $this->assertSame(429, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Too Many Requests', $body['error']);
    }

    public function testPassesThroughWhenUnderRateLimit(): void
    {
        $redis = $this->createRedisMock();
        $redis->setCardValue(5); // Well under limit
        $middleware = $this->rateLimitedMiddleware($redis);

        $request = new ServerRequest('GET', '/test');
        $request = $request
            ->withAttribute('action_class', TestRateLimitedAction::class)
            ->withAttribute('user_uuid', 'user-uuid-123');

        $expectedResponse = new Response(200);
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $response = $middleware->process($request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUsesDefaultLimitsForAuthenticatedRequest(): void
    {
        $redis = $this->createRedisMock();
        $redis->setCardValue(50); // Under default authenticated limit (100)
        $middleware = $this->rateLimitedMiddleware($redis);

        $request = new ServerRequest('GET', '/test');
        $request = $request
            ->withAttribute('action_class', TestActionWithoutRateLimit::class)
            ->withAttribute('user_uuid', 'user-uuid-123');

        $expectedResponse = new Response(200);
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $response = $middleware->process($request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUsesDefaultLimitsForUnauthenticatedRequest(): void
    {
        $redis = $this->createRedisMock();
        $redis->setCardValue(10); // Under default unauthenticated limit (20)
        $middleware = $this->rateLimitedMiddleware($redis);

        $request = new ServerRequest('POST', '/test');
        $request = $request
            ->withAttribute('action_class', TestActionWithoutRateLimit::class)
            ->withParsedBody(['email' => 'test@example.com']);

        $expectedResponse = new Response(200);
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $response = $middleware->process($request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testIncludesRetryAfterHeader(): void
    {
        $redis = $this->createRedisMock();
        $redis->setCardValue(100); // At limit
        $middleware = $this->rateLimitedMiddleware($redis);

        $request = new ServerRequest('GET', '/test');
        $request = $request
            ->withAttribute('action_class', TestRateLimitedAction::class)
            ->withAttribute('user_uuid', 'user-uuid-123');

        $response = $middleware->process($request, $this->handler);

        $this->assertSame('60', $response->getHeaderLine('Retry-After'));
    }

    public function testResponseContentTypeIsJson(): void
    {
        $redis = $this->createRedisMock();
        $redis->setCardValue(100); // At limit
        $middleware = new RateLimitMiddleware($redis);

        $request = new ServerRequest('GET', '/test');
        $request = $request
            ->withAttribute('action_class', TestRateLimitedAction::class)
            ->withAttribute('user_uuid', 'user-uuid-123');

        $response = $middleware->process($request, $this->handler);

        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testNonExistentActionClassPassesThrough(): void
    {
        $redis = $this->createRedisMock();
        $middleware = new RateLimitMiddleware($redis);

        $request = new ServerRequest('GET', '/test');
        $request = $request->withAttribute('action_class', 'NonExistentClass');

        $expectedResponse = new Response(200);
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $response = $middleware->process($request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testResourceScopeUsesRouteParameterIdentifier(): void
    {
        $redis = $this->createRedisMock();
        $middleware = new RateLimitMiddleware($redis, new RateLimit(10, 60, RateLimit::SCOPE_RESOURCE, 'articleUuid'));
        $request = (new ServerRequest('GET', '/articles/1'))
            ->withAttribute('action_class', TestRateLimitedAction::class)
            ->withAttribute('route_params', new RouteParameters(['articleUuid' => 'article-1']));
        $this->handler->expects($this->once())->method('handle')->willReturn(new Response(200));

        $response = $middleware->process($request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('rate_limit:resource:' . hash('sha256', 'article-1'), $redis->getLastKey());
        $this->assertSame(1, $redis->getZaddCalls());
    }

    public function testResourceScopeWithoutScopeKeyPassesThroughWithoutRedis(): void
    {
        $redis = $this->createRedisMock();
        $middleware = new RateLimitMiddleware($redis, new RateLimit(10, 60, RateLimit::SCOPE_RESOURCE));
        $request = (new ServerRequest('GET', '/articles/1'))
            ->withAttribute('action_class', TestRateLimitedAction::class)
            ->withAttribute('route_params', new RouteParameters(['articleUuid' => 'article-1']));
        $this->handler->expects($this->once())->method('handle')->willReturn(new Response(200));

        $response = $middleware->process($request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($redis->getLastKey());
        $this->assertSame(0, $redis->getZaddCalls());
    }

    public function testUnauthenticatedDefaultLimitFallsBackToForwardedIp(): void
    {
        $redis = $this->createRedisMock();
        $middleware = new RateLimitMiddleware($redis);
        $request = (new ServerRequest('POST', '/login', [], null, '1.1', [
            'HTTP_X_FORWARDED_FOR' => '203.0.113.10, 10.0.0.1',
        ]))
            ->withAttribute('action_class', TestActionWithoutRateLimit::class)
            ->withParsedBody([]);
        $this->handler->expects($this->once())->method('handle')->willReturn(new Response(200));

        $response = $middleware->process($request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('rate_limit:account:' . hash('sha256', '203.0.113.10'), $redis->getLastKey());
    }

    public function testCustomErrorMessageIsReturnedWhenRateLimited(): void
    {
        $redis = $this->createRedisMock();
        $redis->setCardValue(1);
        $middleware = new RateLimitMiddleware($redis, new RateLimit(1, 30, RateLimit::SCOPE_USER, errorMessage: 'Slow down'));
        $request = (new ServerRequest('GET', '/test'))
            ->withAttribute('action_class', TestRateLimitedAction::class)
            ->withAttribute('user_uuid', 'user-uuid-123');

        $response = $middleware->process($request, $this->handler);
        $body = json_decode((string) $response->getBody(), true);

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('30', $response->getHeaderLine('Retry-After'));
        $this->assertSame('Slow down', $body['message']);
    }

    private function rateLimitedMiddleware(RedisInterface $redis): RateLimitMiddleware
    {
        return new RateLimitMiddleware($redis, new RateLimit(100, 60, RateLimit::SCOPE_USER, errorMessage: 'Too Many Requests'));
    }
}

// Test fixtures
#[RateLimit(maxAttempts: 100, windowSeconds: 60, scope: RateLimit::SCOPE_USER)]
class TestRateLimitedAction
{
}

class TestActionWithoutRateLimit
{
}
