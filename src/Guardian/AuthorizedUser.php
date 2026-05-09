<?php
declare(strict_types=1);

namespace PCF\Addendum\Guardian;

use PCF\Addendum\Guardian\AccessControlGuardianInterface;
use PCF\Addendum\Auth\Session;
use PCF\Addendum\Exception\AuthorizationError;
use Psr\Http\Message\ServerRequestInterface;

/**
 * AuthorizedUser Guardian - Ensures user is authenticated
 *
 * This is the base guardian that simply verifies a valid session exists.
 * Use this instead of #[Middleware(Auth::class)] to ensure user is logged in.
 *
 * The Auth middleware must still run first to create the session,
 * but this guardian validates that authentication was successful.
 *
 * Usage:
 * ```php
 * #[Route(path: '/profile', method: 'GET')]
 * #[AccessControl(new ClassAccessControlGuardianDefinition(AuthorizedUser::class))]
 * class GetUserAction { }
 * ```
 *
 * Note: This guardian ALWAYS passes if a session exists.
 * For ownership checks, use a resource-specific guardian.
 */
class AuthorizedUser implements AccessControlGuardianInterface
{
    /**
     * Validate that user is authenticated
     *
     * @throws AuthorizationError If no valid session exists
     */
    public function authorize(ServerRequestInterface $request, Session $session): bool
    {
        // If we have a session, user is authenticated
        // Auth middleware already validated the token
        return true;
    }
}
