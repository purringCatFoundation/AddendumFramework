<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Guardian;

use Pradzikowski\Framework\Auth\Session;
use Pradzikowski\Framework\Exception\PermissionDenied;
use Pradzikowski\Framework\Guardian\AccessControlGuardianInterface;
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
