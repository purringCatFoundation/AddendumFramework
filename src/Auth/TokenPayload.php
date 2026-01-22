<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

use JsonSerializable;

/**
 * TokenPayload using PHP 8.5 asymmetric visibility
 * Properties are publicly readable but can only be written from within the class
 */
class TokenPayload implements JsonSerializable
{
    public function __construct(
        public private(set) string $sub,
        public private(set) int $exp,
        public private(set) string $jti,
        public private(set) int $iat,
        public private(set) ?TokenType $tokenType = null,
        public private(set) ?string $characterUuid = null,
        public private(set) ?string $fingerprintHash = null // SHA1 hash of device fingerprint
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = [
            'sub' => $this->sub,
            'exp' => $this->exp,
            'jti' => $this->jti,
            'iat' => $this->iat,
        ];
        if ($this->tokenType !== null) {
            $data['tokenType'] = $this->tokenType->value;
        }
        if ($this->characterUuid !== null) {
            $data['characterUuid'] = $this->characterUuid;
        }
        if ($this->fingerprintHash !== null) {
            $data['fingerprintHash'] = $this->fingerprintHash;
        }
        if ($this->tokenType !== null) {
            $data['type'] = $this->tokenType->value;
        }
        return $data;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['sub']),
            (int) ($data['exp']),
            (string) ($data['jti']),
            (int) ($data['iat']),
            isset($data['tokenType']) && $data['tokenType'] !== '' ? TokenType::from((string) $data['tokenType']) : null,
            isset($data['characterUuid']) ? (string) $data['characterUuid'] : null,
            isset($data['fingerprintHash']) ? (string) $data['fingerprintHash'] : null
        );
    }

    /**
     * Check if this token has elevated privileges (admin or application)
     */
    public function hasElevatedPrivileges(): bool
    {
        return $this->tokenType?->hasElevatedPrivileges() ?? false;
    }

    /**
     * Check if this token requires ownership validation
     */
    public function requiresOwnershipValidation(): bool
    {
        return $this->tokenType?->requiresOwnershipValidation() ?? true;
    }

    /**
     * Get the token type or default to USER
     */
    public function getTokenType(): TokenType
    {
        return $this->tokenType ?? TokenType::USER;
    }
}

