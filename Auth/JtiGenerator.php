<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Auth;

use Random\RandomException;

/**
 * JtiGenerator service for generating RFC 4122 compliant UUID v4
 *
 * Generates unique JWT Token IDs (jti) using cryptographically secure random bytes.
 * Each token receives a unique identifier for tracking and revocation purposes.
 */
class JtiGenerator
{
    /**
     * Generate a random UUID v4 according to RFC 4122
     *
     * Format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
     * - x: random hexadecimal digit
     * - 4: UUID version 4 marker
     * - y: one of 8, 9, a, or b (variant bits)
     *
     * @return string UUID in standard format (e.g., "550e8400-e29b-41d4-a716-446655440000")
     * @throws RandomException If random bytes generation fails
     */
    public function generate(): string
    {
        // Generate 16 random bytes (128 bits)
        $data = random_bytes(16);

        // Set version (4) in bits 12-15 of 7th byte
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);

        // Set variant (RFC 4122) in bits 6-7 of 9th byte
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Format as UUID string: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
