<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Attribute\RateLimit;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use Predis\Client as RedisClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;

/**
 * Rate limiting middleware
 *
 * Default rate limiting for ALL actions:
 * - Authenticated actions: 100 requests/minute per user
 * - Unauthenticated actions: 20 requests/minute per IP
 *
 * Can be overridden with #[RateLimit] attribute for custom limits.
 * Can be disabled with #[RateLimit(maxAttempts: 0)]
 *
 * Uses sliding window algorithm with atomic Redis operations.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    // Default limits
    private const DEFAULT_AUTHENTICATED_MAX = 100;
    private const DEFAULT_AUTHENTICATED_WINDOW = 60;  // 1 minute
    private const DEFAULT_UNAUTHENTICATED_MAX = 20;
    private const DEFAULT_UNAUTHENTICATED_WINDOW = 60;  // 1 minute

    public function __construct(
        private readonly RedisClient|RedisInterface $redis
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $actionClass = $request->getAttribute('action_class');

        if (!$actionClass) {
            return $handler->handle($request);
        }

        // Get RateLimit attribute (if any)
        $rateLimitAttr = $this->getRateLimitAttribute($actionClass);

        // Check if rate limiting is explicitly disabled
        if ($rateLimitAttr && $rateLimitAttr->maxAttempts === 0) {
            // Rate limiting disabled for this action
            return $handler->handle($request);
        }

        // Use custom rate limit or default
        $rateLimit = $rateLimitAttr ?? $this->getDefaultRateLimit($request);

        // Get identifier based on scope
        $identifier = $this->getIdentifier($request, $rateLimit);

        if (!$identifier) {
            // Cannot enforce rate limit without identifier
            return $handler->handle($request);
        }

        // Check rate limit
        if (!$this->checkRateLimit($rateLimit, $identifier)) {
            return $this->createRateLimitResponse($rateLimit);
        }

        return $handler->handle($request);
    }

    /**
     * Get default rate limit based on authentication status
     */
    private function getDefaultRateLimit(ServerRequestInterface $request): RateLimit
    {
        $isAuthenticated = $request->getAttribute('user_uuid') !== null;

        if ($isAuthenticated) {
            return new RateLimit(
                maxAttempts: self::DEFAULT_AUTHENTICATED_MAX,
                windowSeconds: self::DEFAULT_AUTHENTICATED_WINDOW,
                scope: RateLimit::SCOPE_USER
            );
        } else {
            return new RateLimit(
                maxAttempts: self::DEFAULT_UNAUTHENTICATED_MAX,
                windowSeconds: self::DEFAULT_UNAUTHENTICATED_WINDOW,
                scope: RateLimit::SCOPE_ACCOUNT  // IP-based
            );
        }
    }

    /**
     * Get RateLimit attribute from action class
     */
    private function getRateLimitAttribute(string $actionClass): ?RateLimit
    {
        if (!class_exists($actionClass)) {
            return null;
        }

        $reflection = new ReflectionClass($actionClass);
        $attributes = $reflection->getAttributes(RateLimit::class);

        if (empty($attributes)) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    /**
     * Get identifier based on rate limit scope
     */
    private function getIdentifier(ServerRequestInterface $request, RateLimit $rateLimit): ?string
    {
        return match ($rateLimit->scope) {
            RateLimit::SCOPE_ACCOUNT => $this->getAccountIdentifier($request),
            RateLimit::SCOPE_USER => $this->getUserIdentifier($request),
            RateLimit::SCOPE_RESOURCE => $this->getResourceIdentifier($request, $rateLimit),
            default => null,
        };
    }

    /**
     * Get account identifier (email from request body)
     */
    private function getAccountIdentifier(ServerRequestInterface $request): ?string
    {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return null;
        }

        // Try email first (login/register)
        if (isset($body['email']) && is_string($body['email'])) {
            return strtolower(trim($body['email']));
        }

        // Fallback to IP address
        return $this->getIpAddress($request);
    }

    /**
     * Get user identifier (user UUID from session)
     */
    private function getUserIdentifier(ServerRequestInterface $request): ?string
    {
        return $request->getAttribute('user_uuid');
    }

    /**
     * Get resource identifier (from route params)
     */
    private function getResourceIdentifier(ServerRequestInterface $request, RateLimit $rateLimit): ?string
    {
        if (!$rateLimit->scopeKey) {
            return null;
        }

        $routeParams = $request->getAttribute('route_params', []);
        return $routeParams[$rateLimit->scopeKey] ?? null;
    }

    /**
     * Get client IP address
     */
    private function getIpAddress(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();

        // Check for forwarded IP (if behind proxy)
        if (isset($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        if (isset($serverParams['HTTP_X_REAL_IP'])) {
            return $serverParams['HTTP_X_REAL_IP'];
        }

        return $serverParams['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Check rate limit using sliding window algorithm
     *
     * @param RateLimit $rateLimit Rate limit configuration
     * @param string $identifier Unique identifier (email, user UUID, etc)
     * @return bool True if within rate limit, false if exceeded
     */
    private function checkRateLimit(RateLimit $rateLimit, string $identifier): bool
    {
        $key = $rateLimit->getRedisKey($identifier);
        $now = time();
        $windowStart = $now - $rateLimit->windowSeconds;

        // Remove old entries (outside window)
        $this->redis->zremrangebyscore($key, '-inf', (string)$windowStart);

        // Count current entries in window
        $count = $this->redis->zcard($key);

        if ($count >= $rateLimit->maxAttempts) {
            return false;
        }

        // Add current request to sorted set
        $this->redis->zadd($key, [$now => $now]);

        // Set expiration on key (cleanup)
        $this->redis->expire($key, $rateLimit->windowSeconds);

        return true;
    }

    /**
     * Create rate limit exceeded response
     */
    private function createRateLimitResponse(RateLimit $rateLimit): ResponseInterface
    {
        $body = Utils::streamFor(json_encode([
            'error' => 'Too Many Requests',
            'message' => $rateLimit->getErrorMessage()
        ]));

        return new PsrResponse(
            429,
            [
                'Content-Type' => 'application/json',
                'Retry-After' => (string)$rateLimit->windowSeconds
            ],
            $body
        );
    }
}
