<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

use PCF\Addendum\Config\JwtConfig;
use PCF\Addendum\Exception\InvalidCredentialsException;
use PCF\Addendum\Exception\UnauthorizedException;
use PCF\Addendum\Repository\User\AdminRepositoryInterface;

class AuthService
{
    public function __construct(
        private AuthRepositoryInterface $repository,
        private TokenValidationRepository $tokenValidationRepository,
        private AdminRepositoryInterface $adminRepository,
        private JwtConfig $jwtConfig,
        private JtiGenerator $jtiGenerator
    ) {
    }

    /**
     * Register new user account
     *
     * @param string $email User email address
     * @param string $password User password
     */
    public function register(string $email, string $password): RegisteredUser
    {
        return $this->repository->createUser($email, $password);
    }

    /**
     * Authenticate user and return token pair
     *
     * @param string $email User email address
     * @param string $password User password
     * @param string $fingerprint Device fingerprint from client
     * @throws InvalidCredentialsException When credentials are invalid
     */
    public function login(string $email, string $password, string $fingerprint): TokenPair
    {
        $user = $this->repository->findUserByEmail($email);
        $hash = null;

        if ($user) {
            $hash = $this->repository->latestPasswordHash($user->id);
        }

        $dummyHash = '$argon2id$v=19$m=65536,t=4,p=3$c29tZXNhbHQxMjM0NTY3OA$hash';
        $verificationHash = $hash ?: $dummyHash;
        $passwordValid = password_verify($password, $verificationHash);

        if (!$user || !$hash || !$passwordValid) {
            throw new InvalidCredentialsException();
        }

        return $this->createTokenPair($user->uuid, $fingerprint);
    }

    /**
     * Create new access token using refresh token
     *
     * @param string $token Valid refresh token
     * @param string $fingerprint Device fingerprint from client
     * @throws UnauthorizedException When token is invalid or revoked
     */
    public function refresh(string $token, string $fingerprint): TokenPair
    {
        $payload = Jwt::decode($token, $this->jwtConfig->secret);

        if ($payload->tokenType !== TokenType::USER_REFRESH) {
            throw new UnauthorizedException('Invalid token type');
        }

        if (!$this->tokenValidationRepository->isTokenValid($payload->sub, $payload->iat)) {
            throw new UnauthorizedException('Refresh token has been revoked');
        }

        // Verify fingerprint matches token
        $fingerprintHash = sha1($fingerprint);
        if ($payload->fingerprintHash !== null && $payload->fingerprintHash !== $fingerprintHash) {
            throw new UnauthorizedException('Device fingerprint mismatch');
        }

        return $this->createTokenPair($payload->sub, $fingerprint);
    }

    /**
     * Revoke all tokens for user (logout)
     *
     * @param string $userUuid User identifier
     * @param string $reason Reason for token revocation
     */
    public function logout(string $userUuid, string $reason = 'user_logout'): void
    {
        $this->tokenValidationRepository->revokeUserTokens($userUuid, $reason);
    }

    /**
     * Revoke all tokens for user across all devices
     *
     * @param string $userUuid User identifier
     * @param string $reason Reason for token revocation
     */
    public function logoutFromAllDevices(string $userUuid, string $reason = 'user_logout_all'): void
    {
        $this->tokenValidationRepository->revokeUserTokens($userUuid, $reason);
    }

    /**
     * Create CHARACTER token pair for selected character
     *
     * This allows user to switch from USER context to CHARACTER context
     * for in-game actions.
     *
     * @param string $userUuid User identifier (owner of character)
     * @param string $characterUuid Character identifier
     * @param string $fingerprint Device fingerprint from client
     */
    public function selectCharacter(string $userUuid, string $characterUuid, string $fingerprint): TokenPair
    {
        $now = time();
        $fingerprintHash = sha1($fingerprint);

        // Check if user is admin (admin maintains elevated privileges even in character context)
        $isAdmin = $this->adminRepository->isUserAdmin($userUuid);
        $tokenType = $isAdmin ? TokenType::ADMIN : TokenType::CHARACTER;

        $accessToken = Jwt::encode(new TokenPayload(
            $userUuid,
            $now + $this->jwtConfig->accessTokenLifetime,
            $this->jtiGenerator->generate(),
            $now,
            $tokenType,
            $characterUuid,
            $fingerprintHash
        ), $this->jwtConfig->secret);

        $refreshToken = Jwt::encode(new TokenPayload(
            $userUuid,
            $now + $this->jwtConfig->refreshTokenLifetime,
            $this->jtiGenerator->generate(),
            $now,
            $tokenType,
            $characterUuid,
            $fingerprintHash
        ), $this->jwtConfig->secret);

        return new TokenPair(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            expiresIn: $this->jwtConfig->accessTokenLifetime,
            tokenType: $tokenType->value,
            isAdmin: $isAdmin,
            characterUuid: $characterUuid
        );
    }

    /**
     * Create access and refresh token pair for user
     *
     * Automatically checks if user is admin and sets appropriate token type:
     * - If user has active admin privileges → TokenType::ADMIN
     * - Otherwise → TokenType::USER
     *
     * @param string $userUuid User identifier
     * @param string $fingerprint Device fingerprint from client
     */
    private function createTokenPair(string $userUuid, string $fingerprint): TokenPair
    {
        $now = time();
        $fingerprintHash = sha1($fingerprint);

        // Check if user is admin
        $isAdmin = $this->adminRepository->isUserAdmin($userUuid);
        $tokenType = $isAdmin ? TokenType::ADMIN : TokenType::USER;

        $accessToken = Jwt::encode(new TokenPayload(
            $userUuid,
            $now + $this->jwtConfig->accessTokenLifetime,
            $this->jtiGenerator->generate(),
            $now,
            $tokenType,
            null,
            $fingerprintHash
        ), $this->jwtConfig->secret);

        $refreshToken = Jwt::encode(new TokenPayload(
            $userUuid,
            $now + $this->jwtConfig->refreshTokenLifetime,
            $this->jtiGenerator->generate(),
            $now,
            TokenType::USER_REFRESH,
            null,
            $fingerprintHash
        ), $this->jwtConfig->secret);

        return new TokenPair(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            expiresIn: $this->jwtConfig->accessTokenLifetime,
            tokenType: 'Bearer',
            isAdmin: $isAdmin
        );
    }
}
