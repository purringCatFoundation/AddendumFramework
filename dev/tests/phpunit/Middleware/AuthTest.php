<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Middleware;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Auth\ApplicationTokenValidator;
use PCF\Addendum\Auth\Jwt;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Auth\TokenValidationRepository;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Tests\Support\JwtKeyPair;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthTest extends TestCase
{
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
            ->with(TokenType::USER, 'user-1', 'jti-1', self::isInt(), 'session-1')
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
                    && $request->getAttribute('session_id') === 'session-1'
                    && $request->getAttribute('session') !== null;
            }))
            ->willReturn(new Response(200));

        $request = (new ServerRequest('GET', '/'))->withHeader('Authorization', 'Bearer ' . $this->jwt());
        $response = $this->middleware($tokenRepository)->process($request, $handler);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testRefreshTokenReturnsUnauthorized(): void
    {
        $handler = $this->handler();
        $handler->expects(self::never())->method('handle');

        $request = (new ServerRequest('GET', '/'))->withHeader('Authorization', 'Bearer ' . $this->jwt(TokenType::USER_REFRESH));
        $response = $this->middleware()->process($request, $handler);
        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('Invalid token type', $payload['message']);
    }

    public function testUnknownApplicationTokenReturnsUnauthorized(): void
    {
        $tokenRepository = $this->createMock(TokenValidationRepository::class);
        $tokenRepository->expects(self::never())->method('isTokenValid');
        $applicationTokenValidator = $this->createMock(ApplicationTokenValidator::class);
        $handler = $this->handler();
        $handler->expects(self::never())->method('handle');
        $token = $this->jwt(TokenType::APPLICATION);

        $applicationTokenValidator->expects(self::once())
            ->method('isKnown')
            ->with('jti-1', $token)
            ->willReturn(false);

        $request = (new ServerRequest('GET', '/'))->withHeader('Authorization', 'Bearer ' . $token);
        $response = $this->middleware($tokenRepository, $applicationTokenValidator)->process($request, $handler);
        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('Unknown application token', $payload['message']);
    }

    private function jwt(string $tokenType = TokenType::USER): string
    {
        $keyPair = JwtKeyPair::shared();

        return Jwt::encode(new TokenPayload(
            sub: 'user-1',
            exp: time() + 3600,
            jti: 'jti-1',
            iat: time(),
            tokenType: $tokenType,
            fingerprintHash: 'fingerprint-hash',
            sid: $tokenType === TokenType::APPLICATION ? null : 'session-1'
        ), $keyPair->privateKeyPath, $keyPair->privateKeyPassphrase);
    }

    private function middleware(
        ?TokenValidationRepository $tokenRepository = null,
        ?ApplicationTokenValidator $applicationTokenValidator = null
    ): Auth
    {
        return new Auth(
            JwtKeyPair::shared()->publicKeyPath,
            $tokenRepository ?? $this->createMock(TokenValidationRepository::class),
            $applicationTokenValidator ?? $this->createMock(ApplicationTokenValidator::class)
        );
    }

    private function handler(): RequestHandlerInterface
    {
        return $this->createMock(RequestHandlerInterface::class);
    }
}
