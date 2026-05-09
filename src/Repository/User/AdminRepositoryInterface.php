<?php
declare(strict_types=1);

namespace PCF\Addendum\Repository\User;

use PCF\Addendum\Entity\User\ActiveAdminCollection;
use PCF\Addendum\Entity\User\Admin;
use PCF\Addendum\Entity\User\AdminAuditTrail;
use PCF\Addendum\Entity\User\AdminStatistics;

/**
 * Interface for Admin database operations
 */
interface AdminRepositoryInterface
{
    public function isUserAdmin(string $userUuid): bool;

    public function grantAdminPrivileges(
        string $userUuid,
        ?string $grantedByUserUuid = null,
        ?string $reason = null
    ): Admin;

    public function revokeAdminPrivileges(
        string $userUuid,
        ?string $revokedByUserUuid = null,
        ?string $reason = null
    ): bool;

    public function getAdminByUserUuid(string $userUuid): ?Admin;

    public function getAdminByUuid(string $uuid): ?Admin;

    public function listActiveAdmins(): ActiveAdminCollection;

    public function getStatistics(): AdminStatistics;

    public function getAuditTrail(string $userUuid): AdminAuditTrail;
}
