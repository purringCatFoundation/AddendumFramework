<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Auth;

use PCF\Addendum\Auth\AuthRepositoryInterface;
use PCF\Addendum\Auth\AuthService;
use PCF\Addendum\Auth\JtiGenerator;
use PCF\Addendum\Auth\Jwt;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Auth\TokenValidationRepository;
use PCF\Addendum\Auth\UserIdentity;
use PCF\Addendum\Config\JwtConfig;
use PCF\Addendum\Exception\UnauthorizedException;
use PCF\Addendum\Repository\User\AdminRepositoryInterface;
use PCF\Addendum\Tests\Support\JwtKeyPair;
use PHPUnit\Framework\TestCase;

final class SessionLifecycleTest extends TestCase
{
    public function testLoginPairsShareSessionIdButSeparateLoginsAndTokensDoNot(): void
    {
        $service = $this->service();
        $first = $service->login('user@example.com', 'password', 'phone');
        $second = $service->login('user@example.com', 'password', 'desktop');
        $keyPair = JwtKeyPair::shared();
        $access = Jwt::decode($first->accessToken, $keyPair->publicKeyPath)->jsonSerialize();
        $refresh = Jwt::decode($first->refreshToken, $keyPair->publicKeyPath)->jsonSerialize();
        $other = Jwt::decode($second->accessToken, $keyPair->publicKeyPath)->jsonSerialize();

        self::assertArrayHasKey('sid', $access);
        self::assertNotSame('', $access['sid']);
        self::assertSame($access['sid'], $refresh['sid']);
        self::assertNotSame($access['sid'], $other['sid']);
        self::assertNotSame($access['jti'], $refresh['jti']);
    }

    public function testRefreshingKeepsSessionIdAndIssuesNewTokenIds(): void
    {
        $service = $this->service();
        $initial = $service->login('user@example.com', 'password', 'phone');
        $renewed = $service->refresh($initial->refreshToken, 'phone');
        $keyPair = JwtKeyPair::shared();
        $before = Jwt::decode($initial->refreshToken, $keyPair->publicKeyPath)->jsonSerialize();
        $access = Jwt::decode($renewed->accessToken, $keyPair->publicKeyPath)->jsonSerialize();
        $refresh = Jwt::decode($renewed->refreshToken, $keyPair->publicKeyPath)->jsonSerialize();

        self::assertArrayHasKey('sid', $before);
        self::assertSame($before['sid'], $access['sid']);
        self::assertSame($before['sid'], $refresh['sid']);
        self::assertNotSame($before['jti'], $refresh['jti']);
        self::assertNotSame($access['jti'], $refresh['jti']);
    }

    public function testLogoutWithoutSessionIdDoesNotRevokeAllUserTokens(): void
    {
        $repository = $this->createMock(TokenValidationRepository::class);
        $repository->expects(self::never())->method('revokeUserTokens');
        $repository->expects(self::never())->method('revokeToken');
        $repository->expects(self::never())->method('revokeSession');
        $this->expectException(UnauthorizedException::class);

        $this->service($repository)->logout(new TokenPayload('user-1', time() + 3600, 'jti-1', time(), TokenType::USER));
    }

    public function testApplicationTokenCannotLogoutUserSession(): void
    {
        $this->expectException(UnauthorizedException::class);
        $this->service()->logout(new TokenPayload(
            sub: 'external-api', exp: time() + 3600, jti: 'app-jti', iat: time(),
            tokenType: TokenType::APPLICATION, sid: 'session-1'
        ));
    }

    private function service(?TokenValidationRepository $revocations = null): AuthService
    {
        $users = $this->createStub(AuthRepositoryInterface::class);
        $users->method('findUserByEmail')->willReturn(new UserIdentity(1, 'user-1', 'user@example.com'));
        $users->method('latestPasswordHash')->willReturn(password_hash('password', PASSWORD_ARGON2ID));
        $admins = $this->createStub(AdminRepositoryInterface::class);
        $admins->method('isUserAdmin')->willReturn(false);
        $revocations ??= $this->createStub(TokenValidationRepository::class);
        $revocations->method('isTokenValid')->willReturn(true);
        $keys = JwtKeyPair::shared();

        return new AuthService(
            $users,
            $revocations,
            $admins,
            new JwtConfig($keys->privateKeyPath, $keys->publicKeyPath, null, 3600, 86400),
            new JtiGenerator()
        );
    }
}
