<?php
declare(strict_types=1);

namespace PCF\Addendum\Exception;

/**
 * PermissionDenied exception - 403 Forbidden
 *
 * Thrown when a user is authenticated but lacks permission to access a resource.
 * This indicates the user's identity is known, but they don't have the necessary
 * privileges or ownership to perform the requested action.
 *
 * Example scenarios:
 * - User trying to access another user's character
 * - Character trying to transfer an item they don't own
 * - User without admin privileges trying to access admin endpoints
 */
class PermissionDenied extends \RuntimeException
{
    private const HTTP_STATUS_CODE = 403;

    public function __construct(
        string $message = 'Permission denied',
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
     * Create exception for insufficient privileges
     */
    public static function insufficientPrivileges(string $resource = 'this resource'): self
    {
        return new self("Insufficient privileges to access {$resource}");
    }

    /**
     * Create exception for ownership requirement
     */
    public static function notOwner(string $resourceType, string $resourceId): self
    {
        return new self("You do not own {$resourceType}: {$resourceId}");
    }

    /**
     * Create exception for missing permission
     */
    public static function missingPermission(string $permission, string $resource): self
    {
        return new self("Missing '{$permission}' permission for {$resource}");
    }

    /**
     * Create exception for admin-only access
     */
    public static function adminOnly(): self
    {
        return new self('This action requires administrator privileges');
    }

    /**
     * Get error response data
     */
    public function toArray(): array
    {
        return [
            'error' => 'Permission Denied',
            'message' => $this->getMessage(),
            'statusCode' => $this->getHttpStatusCode(),
        ];
    }
}
