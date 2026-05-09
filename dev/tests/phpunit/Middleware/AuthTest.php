<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Middleware;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Auth\Jwt;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Auth\TokenValidationRepository;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Repository\User\ApplicationTokenRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthTest extends TestCase
{
    private const string SECRET = '0123456789abcdef0123456789abcdef';

    public function testMissingBearerTokenReturnsUnauthorized(): void
    {
        $handler = $this->handler();
        $handler->expects(self::never())->method('handle');

        $response = $this->middleware()->process(new ServerRequest('GET', '/'), $handler);
        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('Missing or invalid authorization header', $payload['message']);
    }

    public function testInvalidJwtReturnsUnauthorized(): void
    {
        $handler = $this->handler();
        $handler->expects(self::never())->method('handle');

        $request = (new ServerRequest('GET', '/'))->withHeader('Authorization', 'Bearer not-a-jwt');
        $response = $this->middleware()->process($request, $handler);
        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('Invalid token', $payload['message']);
    }

    public function testRevokedUserTokenReturnsUnauthorized(): void
    {
        $tokenRepository = $this->createMock(TokenValidationRepository::class);
        $tokenRepository->expects(self::once())
            ->method('isTokenValid')
            ->with('user-1', self::isType('int'))
            ->willReturn(false);
        $handler = $this->handler();
        $handler->expects(self::never())->method('handle');

        $request = (new ServerRequest('GET', '/'))->withHeader('Authorization', 'Bearer ' . $this->jwt());
        $response = $this->middleware($tokenRepository)->process($request, $handler);
        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('Token has been revoked', $payload['message']);
    }

    public function testValidUserTokenAddsRequestAttributesAndSession(): void
    {
        $tokenRepository = $this->createMock(TokenValidationRepository::class);
        $tokenRepository->expects(self::once())->method('isTokenValid')->willReturn(true);
        $handler = $this->handler();
        $handler->expects(self::once())
            ->method('handle')
            ->with(self::callback(static function (ServerRequestInterface $request): bool {
                return $request->getAttribute('user_uuid') === 'user-1'
                    && $request->getAttribute('jti') === 'jti-1'
                    && $request->getAttribute('fingerprint_hash') === 'fingerprint-hash'
                    && $request->getAttribute('token_type') === TokenType::USER
                    && $request->getAttribute('session') !== null;
            }))
            ->willReturn(new Response(200));

        $request = (new ServerRequest('GET', '/'))->withHeader('Authorization', 'Bearer ' . $this->jwt());
        $response = $this->middleware($tokenRepository)->process($request, $handler);

        self::assertSame(200, $response->getStatusCode());
    }

    private function jwt(): string
    {
        return Jwt::encode(new TokenPayload(
            sub: 'user-1',
            exp: time() + 3600,
            jti: 'jti-1',
            iat: time(),
            tokenType: TokenType::USER,
            fingerprintHash: 'fingerprint-hash'
        ), self::SECRET);
    }

    private function middleware(?TokenValidationRepository $tokenRepository = null): Auth
    {
        return new Auth(
            self::SECRET,
            $tokenRepository ?? $this->createMock(TokenValidationRepository::class),
            new ApplicationTokenRepository(new PDO('sqlite::memory:'))
        );
    }

    private function handler(): RequestHandlerInterface
    {
        return $this->createMock(RequestHandlerInterface::class);
    }
}
