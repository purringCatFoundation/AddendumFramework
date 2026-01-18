<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Attribute;

use Attribute;

/**
 * Rate limiting attribute for actions
 *
 * Limits the number of requests to an action based on different scopes:
 * - SCOPE_ACCOUNT: Pre-auth rate limiting by email/username
 * - SCOPE_USER: Post-auth rate limiting by user UUID
 * - SCOPE_RESOURCE: Resource-specific rate limiting
 *
 * Example usage:
 * #[RateLimit(maxAttempts: 5, windowSeconds: 300, scope: RateLimit::SCOPE_ACCOUNT)]
 * class PostSessionAction { }
 *
 * #[RateLimit(maxAttempts: 10, windowSeconds: 3600, scope: RateLimit::SCOPE_USER)]
 * class PostCharacterAction { }
 */
#[Attribute(Attribute::TARGET_CLASS)]
class RateLimit
{
    public const SCOPE_ACCOUNT = 'account';  // Email/username (pre-auth)
    public const SCOPE_USER = 'user';        // User UUID (post-auth)
    public const SCOPE_RESOURCE = 'resource'; // Resource UUID

    public function __construct(
        public readonly int $maxAttempts,
        public readonly int $windowSeconds,
        public readonly string $scope = self::SCOPE_USER,
        public readonly ?string $scopeKey = null,
        public readonly ?string $errorMessage = null
    ) {
        if ($maxAttempts <= 0) {
            throw new \InvalidArgumentException('maxAttempts must be greater than 0');
        }

        if ($windowSeconds <= 0) {
            throw new \InvalidArgumentException('windowSeconds must be greater than 0');
        }

        if (!in_array($scope, [self::SCOPE_ACCOUNT, self::SCOPE_USER, self::SCOPE_RESOURCE], true)) {
            throw new \InvalidArgumentException('Invalid scope');
        }
    }

    /**
     * Get Redis key for rate limiting
     *
     * @param string $identifier The identifier (email, user UUID, resource UUID)
     * @return string Redis key
     */
    public function getRedisKey(string $identifier): string
    {
        return sprintf('rate_limit:%s:%s', $this->scope, hash('sha256', $identifier));
    }

    /**
     * Get error message
     *
     * @return string Error message
     */
    public function getErrorMessage(): string
    {
        if ($this->errorMessage) {
            return $this->errorMessage;
        }

        return sprintf(
            'Rate limit exceeded. Maximum %d attempts per %d seconds.',
            $this->maxAttempts,
            $this->windowSeconds
        );
    }
}
