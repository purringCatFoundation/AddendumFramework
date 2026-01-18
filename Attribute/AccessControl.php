<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Attribute;

use Attribute;
use \Pradzikowski\Framework\Guardian\AccessControlGuardianInterface;

/**
 * AccessControl attribute for declarative authorization using PHP 8.5 features
 *
 * This attribute allows you to specify custom authorization logic using either:
 * 1. A class implementing AccessControlGuardianInterface
 * 2. A callable (function/closure) with signature: fn(Request, Session): bool
 *
 * IMPORTANT: Using this attribute automatically applies:
 * - Auth middleware (for session creation)
 * - AccessControl middleware (for guardian execution)
 *
 * You do NOT need to declare #[Middleware(Auth::class)] or #[Middleware(AccessControl::class)]
 *
 * The guardian/callable receives:
 * - ServerRequestInterface $request - The HTTP request
 * - Session $session - Authenticated session information
 *
 * The guardian/callable should:
 * - Return true if access is granted
 * - Throw PermissionDenied (403) if user lacks permission
 * - Throw AuthorizationError (401) if authorization check fails
 *
 * Examples:
 *
 * ```php
 * // Require authentication only
 * #[AccessControl(AuthorizedUser::class)]
 * class GetUserAction { }
 *
 * // Require ownership
 * #[AccessControl(CharacterOwnerGuardian::class)]
 * class GetCharacterAction { }
 *
 * // Using a callable
 * #[AccessControl([MyAuthService::class, 'checkAccess'])]
 * class MyAction { }
 *
 * // Multiple guards (all must pass)
 * #[AccessControl(AuthorizedUser::class)]
 * #[AccessControl(CharacterOwnerGuardian::class)]
 * class DeleteCharacterAction { }
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class AccessControl implements RequiresMiddleware
{
    /**
     * @param class-string<AccessControlGuardianInterface>|callable $guardian Guardian class or callable
     */
    public function __construct(
        public readonly string|callable $guardian
    ) {
        $this->validate();
    }

    /**
     * Validate the guardian configuration
     */
    private function validate(): void
    {
        // If it's a string, it should be a class name
        if (is_string($this->guardian)) {
            if (!class_exists($this->guardian)) {
                throw new \InvalidArgumentException(
                    "AccessControl guardian class '{$this->guardian}' does not exist"
                );
            }

            $reflection = new \ReflectionClass($this->guardian);

            if (!$reflection->implementsInterface(AccessControlGuardianInterface::class)) {
                throw new \InvalidArgumentException(
                    "AccessControl guardian '{$this->guardian}' must implement AccessControlGuardianInterface"
                );
            }
        }
        // If it's callable, we can't validate signature at this point
        // Runtime validation will happen in the middleware
    }

    /**
     * Get the guardian class name or callable
     */
    public function getGuardian(): string|callable
    {
        return $this->guardian;
    }

    /**
     * Check if guardian is a class
     */
    public function isClass(): bool
    {
        return is_string($this->guardian);
    }

    /**
     * Check if guardian is a callable
     */
    public function isCallable(): bool
    {
        return is_callable($this->guardian);
    }

    /**
     * Get required middleware for this attribute
     *
     * @return array<class-string>
     */
    public function getRequiredMiddleware(): array
    {
        return [
            \Pradzikowski\Game\Middleware\Auth::class,
            \Pradzikowski\Framework\Http\Middleware\AccessControl::class,
        ];
    }
}
