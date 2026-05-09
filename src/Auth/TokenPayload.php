<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

use JsonSerializable;

class TokenPayload implements JsonSerializable
{
    public function __construct(
        public private(set) string $sub,
        public private(set) int $exp,
        public private(set) string $jti,
        public private(set) int $iat,
        public private(set) ?string $tokenType = null,
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
            $data['tokenType'] = $this->tokenType;
        }
        if ($this->fingerprintHash !== null) {
            $data['fingerprintHash'] = $this->fingerprintHash;
        }
        if ($this->tokenType !== null) {
            $data['type'] = $this->tokenType;
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
            isset($data['tokenType']) && $data['tokenType'] !== '' ? (string) $data['tokenType'] : null,
            isset($data['fingerprintHash']) ? (string) $data['fingerprintHash'] : null
        );
    }

    /**
     * Check if this token has elevated privileges (admin or application)
     */
    public function hasElevatedPrivileges(): bool
    {
        return $this->tokenType !== null && TokenType::hasElevatedPrivileges($this->tokenType);
    }

    /**
     * Check if this token requires ownership validation
     */
    public function requiresOwnershipValidation(): bool
    {
        return $this->tokenType === null || TokenType::requiresOwnershipValidation($this->tokenType);
    }

    /**
     * Get the token type or default to USER
     */
    public function getTokenType(): string
    {
        return $this->tokenType ?? TokenType::USER;
    }
}
