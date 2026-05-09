<?php
declare(strict_types=1);

namespace PCF\Addendum\Guardian;

use PCF\Addendum\Auth\Session;
use PCF\Addendum\Exception\AuthorizationError;
use PCF\Addendum\Exception\PermissionDenied;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface for access control guardians
 *
 * Guardians are responsible for authorizing access to protected resources.
 * They receive the HTTP request and authenticated session, and must decide
 * whether to allow or deny access.
 *
 * Guardians should:
 * - Return true if access is granted
 * - Throw PermissionDenied (403) if user lacks permission
 * - Throw AuthorizationError (401) if authorization check fails
 *
 * Built-in guardians:
 * - RequiresAuthGuardian: Simple check that user is authenticated
 * - ObjectOwnerGuardian: Validates object ownership
 * - AdminOnlyGuardian: Only allows admin tokens
 *
 * Usage:
 * ```php
 * #[AccessControl(new ClassAccessControlGuardianDefinition(AdminOnlyGuardian::class))]
 * class AdminPanelAction { }
 * ```
 *
 * Custom guardian example:
 * ```php
 * class GuildMemberGuardian implements AccessControlGuardianInterface
 * {
 *     public function __construct(
 *         private readonly GuildRepositoryInterface $guildRepository
 *     ) {}
 *
 *     public function authorize(ServerRequestInterface $request, Session $session): bool
 *     {
 *         if ($session->hasElevatedPrivileges()) {
 *             return true;
 *         }
 *
 *         $guildUuid = $request->getAttribute('guildUuid');
 *         if (!$this->guildRepository->isUserMember($guildUuid, $session->userUuid)) {
 *             throw PermissionDenied::missingPermission('guild membership', $guildUuid);
 *         }
 *
 *         return true;
 *     }
 * }
 * ```
 */
interface AccessControlGuardianInterface
{
    /**
     * Authorize access to the protected resource
     *
     * @param ServerRequestInterface $request The HTTP request
     * @param Session $session The authenticated session
     * @return bool True if access is granted
     * @throws PermissionDenied When user lacks permission (returns 403)
     * @throws AuthorizationError When authorization check fails (returns 401)
     */
    public function authorize(ServerRequestInterface $request, Session $session): bool;
}
