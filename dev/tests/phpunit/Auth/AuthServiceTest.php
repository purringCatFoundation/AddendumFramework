<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Auth;

use PCF\Addendum\Auth\AuthRepositoryInterface;
use PCF\Addendum\Auth\AuthService;
use PCF\Addendum\Auth\Jwt;
use PCF\Addendum\Auth\JtiGenerator;
use PCF\Addendum\Auth\RegisteredUser;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenPair;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Auth\UserIdentity;
use PCF\Addendum\Auth\TokenValidationRepository;
use PCF\Addendum\Config\JwtConfig;
use PCF\Addendum\Exception\InvalidCredentialsException;
use PCF\Addendum\Exception\UnauthorizedException;
use PCF\Addendum\Repository\User\AdminRepositoryInterface;
use PCF\Addendum\Tests\Support\JwtKeyPair;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    private AuthRepositoryInterface&MockObject $mockAuthRepository;
    private TokenValidationRepository&MockObject $mockTokenValidationRepository;
    private AdminRepositoryInterface&MockObject $mockAdminRepository;
    private JtiGenerator&MockObject $mockJtiGenerator;
    private JwtConfig $jwtConfig;
    private AuthService $authService;

    protected function setUp(): void
    {
        $this->mockAuthRepository = $this->createMock(AuthRepositoryInterface::class);
        $this->mockTokenValidationRepository = $this->createMock(TokenValidationRepository::class);
        $this->mockAdminRepository = $this->createMock(AdminRepositoryInterface::class);
        $this->mockJtiGenerator = $this->createMock(JtiGenerator::class);
        $keyPair = JwtKeyPair::shared();
        $this->jwtConfig = new JwtConfig(
            $keyPair->privateKeyPath,
            $keyPair->publicKeyPath,
            $keyPair->privateKeyPassphrase,
            3600,
            86400
        );

        $this->mockJtiGenerator
            ->method('generate')
            ->willReturn('test-jti-123');

        $this->authService = new AuthService(
            $this->mockAuthRepository,
            $this->mockTokenValidationRepository,
            $this->mockAdminRepository,
            $this->jwtConfig,
            $this->mockJtiGenerator
        );
    }

    public function testRegisterCallsRepository(): void
    {
        $email = 'test@example.com';
        $password = 'password123';
        $expectedResult = new RegisteredUser('test-uuid', $email);

        $this->mockAuthRepository
            ->expects($this->once())
            ->method('createUser')
            ->with($email, $password)
            ->willReturn($expectedResult);

        $result = $this->authService->register($email, $password);

        $this->assertSame($expectedResult, $result);
    }

    public function testLoginWithValidCredentials(): void
    {
        $email = 'test@example.com';
        $password = 'password123';
        $fingerprint = 'test-fingerprint';
        $userUuid = '123e4567-e89b-12d3-a456-426614174000';
        $user = new UserIdentity(1, $userUuid, $email);
        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);

        $this->mockAuthRepository
            ->expects($this->once())
            ->method('findUserByEmail')
            ->with($email)
            ->willReturn($user);

        $this->mockAuthRepository
            ->expects($this->once())
            ->method('latestPasswordHash')
            ->with(1)
            ->willReturn($hashedPassword);

        $this->mockAdminRepository
            ->expects($this->once())
            ->method('isUserAdmin')
            ->with($userUuid)
            ->willReturn(false);

        $result = $this->authService->login($email, $password, $fingerprint);

        $this->assertInstanceOf(TokenPair::class, $result);
        $this->assertNotSame('', $result->accessToken);
        $this->assertNotSame('', $result->refreshToken);
        $this->assertSame(3600, $result->expiresIn);
        $this->assertSame('Bearer', $result->tokenType);
    }

    public function testLoginWithNonExistentUser(): void
    {
        $email = 'nonexistent@example.com';
        $password = 'password123';
        $fingerprint = 'test-fingerprint';

        $this->mockAuthRepository
            ->expects($this->once())
            ->method('findUserByEmail')
            ->with($email)
            ->willReturn(null);

        $this->expectException(InvalidCredentialsException::class);

        $this->authService->login($email, $password, $fingerprint);
    }

    public function testLoginWithInvalidPassword(): void
    {
        $email = 'test@example.com';
        $password = 'wrongpassword';
        $fingerprint = 'test-fingerprint';
        $user = new UserIdentity(1, 'test-uuid', $email);
        $hashedPassword = password_hash('correctpassword', PASSWORD_ARGON2ID);

        $this->mockAuthRepository
            ->expects($this->once())
            ->method('findUserByEmail')
            ->with($email)
            ->willReturn($user);

        $this->mockAuthRepository
            ->expects($this->once())
            ->method('latestPasswordHash')
            ->with(1)
            ->willReturn($hashedPassword);

        $this->expectException(InvalidCredentialsException::class);

        $this->authService->login($email, $password, $fingerprint);
    }

    public function testLoginWithNoPasswordHash(): void
    {
        $email = 'test@example.com';
        $password = 'password123';
        $fingerprint = 'test-fingerprint';
        $user = new UserIdentity(1, 'test-uuid', $email);

        $this->mockAuthRepository
            ->expects($this->once())
            ->method('findUserByEmail')
            ->with($email)
            ->willReturn($user);

        $this->mockAuthRepository
            ->expects($this->once())
            ->method('latestPasswordHash')
            ->with(1)
            ->willReturn(null);

        $this->expectException(InvalidCredentialsException::class);

        $this->authService->login($email, $password, $fingerprint);
    }

    public function testRefreshWithValidToken(): void
    {
        $userUuid = '123e4567-e89b-12d3-a456-426614174000';
        $fingerprint = 'test-fingerprint';
        $fingerprintHash = sha1($fingerprint);
        $now = time();

        $refreshToken = Jwt::encode(new TokenPayload(
            $userUuid,
            $now + 86400,
            'jti-123',
            $now,
            TokenType::USER_REFRESH,
            $fingerprintHash,
            'session-1'
        ), $this->jwtConfig->privateKeyPath, $this->jwtConfig->privateKeyPassphrase);

        $this->mockTokenValidationRepository
            ->expects($this->once())
            ->method('isTokenValid')
            ->with(TokenType::USER_REFRESH, $userUuid, 'jti-123', $now, 'session-1')
            ->willReturn(true);

        $this->mockAdminRepository
            ->expects($this->once())
            ->method('isUserAdmin')
            ->with($userUuid)
            ->willReturn(false);

        $result = $this->authService->refresh($refreshToken, $fingerprint);

        $this->assertInstanceOf(TokenPair::class, $result);
        $this->assertNotSame('', $result->accessToken);
        $this->assertNotSame('', $result->refreshToken);
    }

    public function testRefreshWithInvalidTokenType(): void
    {
        $userUuid = '123e4567-e89b-12d3-a456-426614174000';
        $fingerprint = 'test-fingerprint';
        $now = time();

        // Create access token instead of refresh token
        $accessToken = Jwt::encode(new TokenPayload(
            $userUuid,
            $now + 3600,
            'jti-123',
            $now,
            TokenType::USER, // Wrong type - should be USER_REFRESH
            sid: 'session-1'
        ), $this->jwtConfig->privateKeyPath, $this->jwtConfig->privateKeyPassphrase);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Invalid token type');

        $this->authService->refresh($accessToken, $fingerprint);
    }

    public function testRefreshWithRevokedToken(): void
    {
        $userUuid = '123e4567-e89b-12d3-a456-426614174000';
        $fingerprint = 'test-fingerprint';
        $fingerprintHash = sha1($fingerprint);
        $now = time();

        $refreshToken = Jwt::encode(new TokenPayload(
            $userUuid,
            $now + 86400,
            'jti-123',
            $now,
            TokenType::USER_REFRESH,
            $fingerprintHash,
            'session-1'
        ), $this->jwtConfig->privateKeyPath, $this->jwtConfig->privateKeyPassphrase);

        $this->mockTokenValidationRepository
            ->expects($this->once())
            ->method('isTokenValid')
            ->with(TokenType::USER_REFRESH, $userUuid, 'jti-123', $now, 'session-1')
            ->willReturn(false);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Refresh token has been revoked');

        $this->authService->refresh($refreshToken, $fingerprint);
    }

    public function testLogout(): void
    {
        $reason = 'user_logout';
        $payload = new TokenPayload(
            sub: '123e4567-e89b-12d3-a456-426614174000',
            exp: 1700003600,
            jti: 'jti-123',
            iat: 1700000000,
            tokenType: TokenType::USER,
            sid: 'session-1'
        );

        $this->mockTokenValidationRepository
            ->expects($this->once())
            ->method('revokeSession')
            ->with('session-1', $payload->sub, $reason);

        $this->authService->logout($payload, $reason);
    }

    public function testLogoutFromAllDevices(): void
    {
        $userUuid = '123e4567-e89b-12d3-a456-426614174000';
        $reason = 'user_logout_all';

        $this->mockTokenValidationRepository
            ->expects($this->once())
            ->method('revokeUserTokens')
            ->with($userUuid, $reason);

        $this->authService->logoutFromAllDevices($userUuid, $reason);
    }

    public function testLoginReturnsAdminTokenForAdminUser(): void
    {
        $email = 'admin@example.com';
        $password = 'password123';
        $fingerprint = 'test-fingerprint';
        $userUuid = '123e4567-e89b-12d3-a456-426614174000';
        $user = new UserIdentity(1, $userUuid, $email);
        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);

        $this->mockAuthRepository
            ->expects($this->once())
            ->method('findUserByEmail')
            ->with($email)
            ->willReturn($user);

        $this->mockAuthRepository
            ->expects($this->once())
            ->method('latestPasswordHash')
            ->with(1)
            ->willReturn($hashedPassword);

        $this->mockAdminRepository
            ->expects($this->once())
            ->method('isUserAdmin')
            ->with($userUuid)
            ->willReturn(true);

        $result = $this->authService->login($email, $password, $fingerprint);

        $this->assertTrue($result->isAdmin());
    }
}
