<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Middleware;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Auth\Jwt;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Auth\TokenValidationRepository;
use PCF\Addendum\Http\Middleware\RefreshAuth;
use PCF\Addendum\Http\Middleware\RequestReplayCache;
use PCF\Addendum\Http\Middleware\RequestSignature;
use PCF\Addendum\Http\MiddlewareRequestHandler;
use PCF\Addendum\Tests\Support\JwtKeyPair;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RefreshAuthTest extends TestCase
{
    private const SECRET = 'test-request-signature-secret-32-bytes-minimum';

    public function testSignedRefreshRequestPassesWithoutAnyAccessToken(): void
    {
        $repository = $this->createMock(TokenValidationRepository::class);
        $repository->expects(self::once())->method('isTokenValid')
            ->with(TokenType::USER_REFRESH, 'user-1', 'refresh-jti', self::isInt(), 'session-1')
            ->willReturn(true);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')
            ->with(self::callback(static fn(ServerRequestInterface $request): bool =>
                $request->getAttribute('jwt_token') !== null
                && $request->getAttribute('session_id') === 'session-1'
                && $request->getAttribute('token_type') === TokenType::USER_REFRESH))
            ->willReturn(new Response(200));

        self::assertSame(200, $this->pipeline($repository, $handler)->handle($this->request())->getStatusCode());
    }

    #[DataProvider('nonRefreshTypes')]
    public function testNonRefreshTokenCannotReachRefreshHandler(string $type): void
    {
        $repository = $this->createMock(TokenValidationRepository::class);
        $repository->expects(self::never())->method('isTokenValid');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        self::assertSame(401, $this->pipeline($repository, $handler)->handle($this->request($type))->getStatusCode());
    }

    public static function nonRefreshTypes(): iterable
    {
        yield 'user access' => [TokenType::USER];
        yield 'admin access' => [TokenType::ADMIN];
        yield 'application access' => [TokenType::APPLICATION];
    }

    public function testRevokedSessionCannotRefresh(): void
    {
        $repository = $this->createStub(TokenValidationRepository::class);
        $repository->method('isTokenValid')->willReturn(false);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        self::assertSame(401, $this->pipeline($repository, $handler)->handle($this->request())->getStatusCode());
    }

    public function testExpiredRefreshTokenCannotReachHandler(): void
    {
        $repository = $this->createMock(TokenValidationRepository::class);
        $repository->expects(self::never())->method('isTokenValid');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        self::assertSame(401, $this->pipeline($repository, $handler)->handle($this->request(expiresAt: time() - 1))->getStatusCode());
    }

    public function testRefreshRetainsSignatureAndFingerprintProtection(): void
    {
        $repository = $this->createStub(TokenValidationRepository::class);
        $repository->method('isTokenValid')->willReturn(true);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');
        $pipeline = $this->pipeline($repository, $handler);

        self::assertSame(403, $pipeline->handle($this->request()->withHeader('X-Request-Signature', 'wrong'))->getStatusCode());
        self::assertSame(403, $pipeline->handle($this->request(fingerprint: 'other-device'))->getStatusCode());
        self::assertSame(400, $pipeline->handle($this->request()->withoutHeader('X-Request-Nonce'))->getStatusCode());
    }

    public function testSignedRefreshRequestCannotBeReplayed(): void
    {
        $repository = $this->createStub(TokenValidationRepository::class);
        $repository->method('isTokenValid')->willReturn(true);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturn(new Response(200));
        $pipeline = $this->pipeline($repository, $handler);
        $request = $this->request();

        self::assertSame(200, $pipeline->handle($request)->getStatusCode());
        self::assertSame(409, $pipeline->handle($request)->getStatusCode());
    }

    private function request(
        string $type = TokenType::USER_REFRESH,
        ?int $expiresAt = null,
        string $fingerprint = 'device'
    ): ServerRequest {
        $keys = JwtKeyPair::shared();
        $payload = new TokenPayload(
            sub: 'user-1', exp: $expiresAt ?? time() + 3600, jti: 'refresh-jti', iat: time() - 3600,
            tokenType: $type, fingerprintHash: sha1('device'), sid: 'session-1'
        );
        $token = Jwt::encode($payload, $keys->privateKeyPath);
        $timestamp = (string) time();
        $nonce = bin2hex(random_bytes(16));
        $target = '/v1/session-refreshes';
        $key = hash_hmac('sha256', $payload->jti . $payload->fingerprintHash, self::SECRET);
        $signature = hash_hmac('sha256', $timestamp . $fingerprint . 'POST' . $target . $nonce, $key);

        return (new ServerRequest('POST', $target))
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Request-Fingerprint', $fingerprint)
            ->withHeader('X-Request-Timestamp', $timestamp)
            ->withHeader('X-Request-Nonce', $nonce)
            ->withHeader('X-Request-Signature', $signature);
    }

    private function pipeline(TokenValidationRepository $repository, RequestHandlerInterface $handler): RequestHandlerInterface
    {
        $replay = $this->createStub(RequestReplayCache::class);
        $replay->method('requiresNonce')->willReturn(true);
        $used = [];
        $replay->method('has')->willReturnCallback(static function (string $key) use (&$used): bool {
            return isset($used[$key]);
        });
        $replay->method('set')->willReturnCallback(static function (string $key) use (&$used): void {
            $used[$key] = true;
        });

        return new MiddlewareRequestHandler(
            new RefreshAuth(JwtKeyPair::shared()->publicKeyPath, $repository),
            new MiddlewareRequestHandler(new RequestSignature(self::SECRET, $replay), $handler)
        );
    }
}
