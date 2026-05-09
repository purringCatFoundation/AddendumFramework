<?php
declare(strict_types=1);

namespace PCF\Addendum\Attribute;

use Attribute;
use Ds\Vector;
use PCF\Addendum\Http\Middleware\AccessControl as AccessControlMiddleware;
use PCF\Addendum\Http\Middleware\AccessControlGuardianDefinitionInterface;
use PCF\Addendum\Http\Middleware\Auth;

/**
 * AccessControl attribute for declarative authorization using PHP 8.5 features
 *
 * This attribute allows you to specify custom authorization logic using a guardian definition object.
 *
 * IMPORTANT: Using this attribute automatically applies:
 * - Auth middleware (for session creation)
 * - AccessControl middleware (for guardian execution)
 *
 * You do NOT need to declare #[Middleware(Auth::class)] or #[Middleware(AccessControl::class)]
 *
 * The guardian receives:
 * - ServerRequestInterface $request - The HTTP request
 * - Session $session - Authenticated session information
 *
 * The guardian should:
 * - Return true if access is granted
 * - Throw PermissionDenied (403) if user lacks permission
 * - Throw AuthorizationError (401) if authorization check fails
 *
 * Examples:
 *
 * ```php
 * // Require authentication only
 * #[AccessControl(new ClassAccessControlGuardianDefinition(AuthorizedUser::class))]
 * class GetUserAction { }
 *
 * // Require ownership
 * #[AccessControl(new ClassAccessControlGuardianDefinition(ResourceOwnerGuardian::class))]
 * class GetResourceAction { }
 *
 * // Multiple guards (all must pass)
 * #[AccessControl(new ClassAccessControlGuardianDefinition(AuthorizedUser::class))]
 * #[AccessControl(new ClassAccessControlGuardianDefinition(ResourceOwnerGuardian::class))]
 * class DeleteResourceAction { }
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class AccessControl implements RequiresMiddleware
{
    public function __construct(
        public readonly AccessControlGuardianDefinitionInterface $guardian
    ) {
    }

    /**
     * Get the guardian definition
     */
    public function getGuardian(): AccessControlGuardianDefinitionInterface
    {
        return $this->guardian;
    }

    public function getGuardianDefinition(): AccessControlGuardianDefinitionInterface
    {
        return $this->guardian;
    }

    /**
     * Get required middleware for this attribute
     *
     * @return Vector<class-string>
     */
    public function getRequiredMiddleware(): Vector
    {
        return new Vector([
            Auth::class,
            AccessControlMiddleware::class,
        ]);
    }
}
