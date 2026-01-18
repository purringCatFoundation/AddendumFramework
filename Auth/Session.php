<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Auth;

/**
 * Session class containing authenticated user information using PHP 8.5 features
 *
 * This class encapsulates all information extracted from the JWT token
 * and provides convenient methods for authorization checks.
 */
final class Session
{
    /**
     * Computed property: Check if session has elevated privileges
     */
    public bool $hasElevatedPrivileges {
        get => $this->tokenType->hasElevatedPrivileges();
    }

    /**
     * Computed property: Check if session requires ownership validation
     */
    public bool $requiresOwnershipValidation {
        get => $this->tokenType->requiresOwnershipValidation();
    }

    /**
     * Computed property: Check if this is an admin session
     */
    public bool $isAdmin {
        get => $this->tokenType === TokenType::ADMIN;
    }

    /**
     * Computed property: Check if this is an application session
     */
    public bool $isApplication {
        get => $this->tokenType === TokenType::APPLICATION;
    }

    /**
     * Computed property: Check if this is a user session
     */
    public bool $isUser {
        get => $this->tokenType === TokenType::USER;
    }

    /**
     * Computed property: Check if this is a character session
     */
    public bool $isCharacter {
        get => $this->tokenType === TokenType::CHARACTER;
    }

    public function __construct(
        public readonly string $userUuid,
        public readonly TokenType $tokenType,
        public readonly ?string $characterUuid = null,
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
            characterUuid: $payload->characterUuid,
            tokenIssuedAt: $payload->iat,
            tokenExpiresAt: $payload->exp,
            tokenId: $payload->jti
        );
    }

    /**
     * Create session from request attributes (set by Auth middleware)
     */
    public static function fromRequest(\Psr\Http\Message\ServerRequestInterface $request): self
    {
        $tokenPayload = $request->getAttribute('token_payload');

        if ($tokenPayload instanceof TokenPayload) {
            return self::fromTokenPayload($tokenPayload);
        }

        // Fallback: construct from individual attributes
        $tokenType = $request->getAttribute('token_type');
        if (!$tokenType instanceof TokenType) {
            $tokenType = TokenType::USER; // Default
        }

        return new self(
            userUuid: (string) $request->getAttribute('user_uuid'),
            tokenType: $tokenType,
            characterUuid: $request->getAttribute('character_uuid'),
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
        return $this->hasElevatedPrivileges;
    }

    /**
     * Check if character UUID matches session character
     */
    public function isCharacterMatch(string $characterUuid): bool
    {
        return $this->characterUuid === $characterUuid;
    }

    /**
     * Get session information as array
     */
    public function toArray(): array
    {
        return [
            'userUuid' => $this->userUuid,
            'tokenType' => $this->tokenType->value,
            'characterUuid' => $this->characterUuid,
            'tokenIssuedAt' => $this->tokenIssuedAt,
            'tokenExpiresAt' => $this->tokenExpiresAt,
            'tokenId' => $this->tokenId,
            'hasElevatedPrivileges' => $this->hasElevatedPrivileges,
            'isAdmin' => $this->isAdmin,
            'isApplication' => $this->isApplication,
            'isUser' => $this->isUser,
            'isCharacter' => $this->isCharacter,
        ];
    }
}
