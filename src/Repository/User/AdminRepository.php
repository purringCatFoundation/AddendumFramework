<?php
declare(strict_types=1);

namespace PCF\Addendum\Repository\User;

use PCF\Addendum\Entity\User\ActiveAdmin;
use PCF\Addendum\Entity\User\ActiveAdminCollection;
use PCF\Addendum\Entity\User\Admin;
use PCF\Addendum\Entity\User\AdminAuditTrail;
use PCF\Addendum\Entity\User\AdminAuditTrailEntry;
use PCF\Addendum\Entity\User\AdminStatistics;
use PDO;

/**
 * Repository for Admin database operations
 */
final class AdminRepository implements AdminRepositoryInterface
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function isUserAdmin(string $userUuid): bool
    {
        $stmt = $this->db->prepare(
            'SELECT is_user_admin(:user_uuid) as is_admin'
        );

        $stmt->execute([':user_uuid' => $userUuid]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (bool) $result['is_admin'];
    }

    public function grantAdminPrivileges(
        string $userUuid,
        ?string $grantedByUserUuid = null,
        ?string $reason = null
    ): Admin {
        try {
            $stmt = $this->db->prepare(
                'SELECT grant_admin_privileges(:user_uuid, :granted_by_user_uuid, :reason) as uuid'
            );

            $stmt->execute([
                ':user_uuid' => $userUuid,
                ':granted_by_user_uuid' => $grantedByUserUuid,
                ':reason' => $reason,
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $uuid = $result['uuid'];

            $admin = $this->getAdminByUuid($uuid);

            if ($admin === null) {
                throw new \RuntimeException('Failed to retrieve created admin record');
            }

            return $admin;
        } catch (\PDOException $e) {
            // Re-throw with more specific message
            if (str_contains($e->getMessage(), 'does not exist')) {
                throw new \RuntimeException("User with UUID {$userUuid} does not exist", 0, $e);
            }

            if (str_contains($e->getMessage(), 'already has active admin privileges')) {
                throw new \RuntimeException("User {$userUuid} already has active admin privileges", 0, $e);
            }

            throw new \RuntimeException('Failed to grant admin privileges: ' . $e->getMessage(), 0, $e);
        }
    }

    public function revokeAdminPrivileges(
        string $userUuid,
        ?string $revokedByUserUuid = null,
        ?string $reason = null
    ): bool {
        $stmt = $this->db->prepare(
            'SELECT revoke_admin_privileges(:user_uuid, :revoked_by_user_uuid, :reason) as revoked'
        );

        $stmt->execute([
            ':user_uuid' => $userUuid,
            ':revoked_by_user_uuid' => $revokedByUserUuid,
            ':reason' => $reason,
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (bool) $result['revoked'];
    }

    public function getAdminByUserUuid(string $userUuid): ?Admin
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM get_admin_by_user_uuid(:user_uuid)'
        );

        $stmt->execute([':user_uuid' => $userUuid]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return Admin::fromDatabaseRow($row);
    }

    public function getAdminByUuid(string $uuid): ?Admin
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM admins WHERE uuid = :uuid'
        );

        $stmt->execute([':uuid' => $uuid]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return Admin::fromDatabaseRow($row);
    }

    public function listActiveAdmins(): ActiveAdminCollection
    {
        $stmt = $this->db->query(
            'SELECT * FROM list_active_admins()'
        );

        $admins = new ActiveAdminCollection();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $admins->add(ActiveAdmin::fromDatabaseRow($row));
        }

        return $admins;
    }

    public function getStatistics(): AdminStatistics
    {
        $stmt = $this->db->query(
            'SELECT * FROM get_admin_statistics()'
        );

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return AdminStatistics::fromDatabaseRow($result);
    }

    public function getAuditTrail(string $userUuid): AdminAuditTrail
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM get_admin_audit_trail(:user_uuid)'
        );

        $stmt->execute([':user_uuid' => $userUuid]);

        $trail = new AdminAuditTrail();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $trail->add(AdminAuditTrailEntry::fromDatabaseRow($row));
        }

        return $trail;
    }
}
