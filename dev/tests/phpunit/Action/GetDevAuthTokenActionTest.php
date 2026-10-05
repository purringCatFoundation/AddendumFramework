<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Action;

use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Attribute\Middleware;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Auth\Session;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Dev\Action\Auth\GetDevAuthTokenAction;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Http\Request;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once dirname(__DIR__, 3) . '/frankenphp/Action/Auth/GetDevAuthTokenAction.php';

final class GetDevAuthTokenActionTest extends TestCase
{
    public function testRouteRequiresAuthMiddleware(): void
    {
        $reflection = new ReflectionClass(GetDevAuthTokenAction::class);
        $route = $reflection->getAttributes(Route::class)[0]->newInstance();
        $middleware = $reflection->getAttributes(Middleware::class)[0]->newInstance();

        self::assertSame('/dev/auth/token', $route->path);
        self::assertSame('GET', $route->method);
        self::assertSame(Auth::class, $middleware->middlewareClass);
    }

    public function testReturnsAuthorizedTokenDetails(): void
    {
        $payload = new TokenPayload(
            sub: 'user-1',
            exp: 1_700_003_600,
            jti: 'jti-1',
            iat: 1_700_000_000,
            tokenType: TokenType::USER,
            fingerprintHash: sha1('postman-dev-fingerprint'),
            sid: 'session-1'
        );
        $session = Session::fromTokenPayload($payload);
        $serverRequest = (new ServerRequest('GET', '/dev/auth/token'))
            ->withHeader('X-Request-Fingerprint', 'postman-dev-fingerprint')
            ->withHeader('X-Request-Timestamp', '1700000001')
            ->withHeader('X-Request-Nonce', 'nonce-1')
            ->withAttribute('user_uuid', $payload->sub)
            ->withAttribute('jti', $payload->jti)
            ->withAttribute('session_id', $payload->sid)
            ->withAttribute('fingerprint_hash', $payload->fingerprintHash)
            ->withAttribute('token_issued_at', $payload->iat)
            ->withAttribute('token_expires_at', $payload->exp)
            ->withAttribute('token_payload', $payload)
            ->withAttribute('token_type', $payload->getTokenType())
            ->withAttribute('session', $session);

        $response = (new GetDevAuthTokenAction())(new Request($serverRequest));
        $data = $response->jsonSerialize();

        self::assertTrue($data['authorized']);
        self::assertSame($payload->jsonSerialize(), $data['token']);
        self::assertSame($session->toArray(), $data['session']);
        self::assertSame('GET', $data['request']['method']);
        self::assertSame('/dev/auth/token', $data['request']['target']);
        self::assertSame('1700000001', $data['request']['signatureTimestamp']);
        self::assertSame('nonce-1', $data['request']['signatureNonce']);
        self::assertSame($payload->jti, $data['attributes']['jti']);
        self::assertSame('session-1', $data['attributes']['sessionId']);
        self::assertSame('session-1', $data['session']['sessionId']);
        self::assertSame($payload->fingerprintHash, $data['attributes']['fingerprintHash']);
    }
}
