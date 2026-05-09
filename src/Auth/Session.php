<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

use BackedEnum;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Session class containing authenticated user information using PHP 8.5 features
 *
 * This class encapsulates all information extracted from the JWT token
 * and provides convenient methods for authorization checks.
 */
final class Session
{
    public function __construct(
        public readonly string $userUuid,
        public readonly string $tokenType,
        public readonly ?int $tokenIssuedAt = null,
        public readonly ?int $tokenExpiresAt = null,
        public readonly ?string $tokenId = null
    ) {
    }

    /**
     * Create session from TokenPayload
     */
    public static function fromTokenPayload(TokenPayload $payload): self
    {
        return new self(
            userUuid: $payload->sub,
            tokenType: $payload->getTokenType(),
            tokenIssuedAt: $payload->iat,
            tokenExpiresAt: $payload->exp,
            tokenId: $payload->jti
        );
    }

    /**
     * Create session from request attributes (set by Auth middleware)
     */
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $tokenPayload = $request->getAttribute('token_payload');

        if ($tokenPayload instanceof TokenPayload) {
            return self::fromTokenPayload($tokenPayload);
        }

        // Fallback: construct from individual attributes
        $tokenType = $request->getAttribute('token_type');
        if ($tokenType instanceof BackedEnum) {
            $tokenType = (string) $tokenType->value;
        }

        if (!is_string($tokenType) || trim($tokenType) === '') {
            $tokenType = TokenType::USER;
        }

        return new self(
            userUuid: (string) $request->getAttribute('user_uuid'),
            tokenType: $tokenType,
            tokenIssuedAt: $request->getAttribute('token_issued_at'),
            tokenExpiresAt: $request->getAttribute('token_expires_at'),
            tokenId: $request->getAttribute('token_id')
        );
    }

    /**
     * Check if session can bypass ownership checks
     */
    public function canBypassOwnership(): bool
    {
        return $this->hasElevatedPrivileges();
    }

    public function hasElevatedPrivileges(): bool
    {
        return TokenType::hasElevatedPrivileges($this->tokenType);
    }

    public function requiresOwnershipValidation(): bool
    {
        return TokenType::requiresOwnershipValidation($this->tokenType);
    }

    public function isAdmin(): bool
    {
        return $this->tokenType === TokenType::ADMIN;
    }

    public function isApplication(): bool
    {
        return $this->tokenType === TokenType::APPLICATION;
    }

    public function isUser(): bool
    {
        return $this->tokenType === TokenType::USER;
    }

    /**
     * Get session information as array
     */
    public function toArray(): array
    {
        return [
            'userUuid' => $this->userUuid,
            'tokenType' => $this->tokenType,
            'tokenIssuedAt' => $this->tokenIssuedAt,
            'tokenExpiresAt' => $this->tokenExpiresAt,
            'tokenId' => $this->tokenId,
            'hasElevatedPrivileges' => $this->hasElevatedPrivileges(),
            'isAdmin' => $this->isAdmin(),
            'isApplication' => $this->isApplication(),
            'isUser' => $this->isUser(),
        ];
    }
}
