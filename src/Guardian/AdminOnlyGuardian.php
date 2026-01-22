<?php
declare(strict_types=1);

namespace PCF\Addendum\Guardian;

use PCF\Addendum\Auth\Session;
use PCF\Addendum\Exception\PermissionDenied;
use PCF\Addendum\Guardian\AccessControlGuardianInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * AdminOnlyGuardian - Restricts access to administrators only
 *
 * This guardian allows access only to ADMIN tokens.
 * All other token types (APPLICATION, USER, CHARACTER) are denied.
 *
 * Usage:
 * ```php
 * #[Route(path: '/admin/users', method: 'GET')]
 * #[Middleware(Auth::class)]
 * #[AccessControl(AdminOnlyGuardian::class)]
 * class ListAllUsersAction { }
 * ```
 */
class AdminOnlyGuardian implements AccessControlGuardianInterface
{
    public function authorize(ServerRequestInterface $request, Session $session): bool
    {
        if ($session->isAdmin) {
            return true;
        }

        throw PermissionDenied::adminOnly();
    }
}
