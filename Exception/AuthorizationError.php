<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Exception;

/**
 * AuthorizationError exception - 401 Unauthorized
 *
 * Thrown when authorization verification fails, typically due to:
 * - Invalid or malformed tokens
 * - Missing required authentication data
 * - Authorization service failures
 * - Session validation errors
 *
 * Note: This is different from PermissionDenied (403).
 * - 401 (AuthorizationError): "Who are you?" - Identity verification failed
 * - 403 (PermissionDenied): "I know who you are, but you can't do that" - Access denied
 */
class AuthorizationError extends \RuntimeException
{
    private const HTTP_STATUS_CODE = 401;

    public function __construct(
        string $message = 'Authorization failed',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, self::HTTP_STATUS_CODE, $previous);
    }

    /**
     * Get HTTP status code for this exception
     */
    public function getHttpStatusCode(): int
    {
        return self::HTTP_STATUS_CODE;
    }

    /**
     * Create exception for missing authentication
     */
    public static function missingAuthentication(): self
    {
        return new self('Authentication required');
    }

    /**
     * Create exception for invalid token
     */
    public static function invalidToken(string $reason = 'Invalid or malformed token'): self
    {
        return new self($reason);
    }

    /**
     * Create exception for expired token
     */
    public static function expiredToken(): self
    {
        return new self('Token has expired');
    }

    /**
     * Create exception for revoked token
     */
    public static function revokedToken(): self
    {
        return new self('Token has been revoked');
    }

    /**
     * Create exception for missing session
     */
    public static function missingSession(): self
    {
        return new self('Session information not found');
    }

    /**
     * Create exception for invalid session data
     */
    public static function invalidSession(string $reason = 'Invalid session data'): self
    {
        return new self($reason);
    }

    /**
     * Get error response data
     */
    public function toArray(): array
    {
        return [
            'error' => 'Unauthorized',
            'message' => $this->getMessage(),
            'statusCode' => $this->getHttpStatusCode(),
        ];
    }
}
