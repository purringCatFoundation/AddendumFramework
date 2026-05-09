<?php
declare(strict_types=1);

namespace PCF\Addendum\Entity\User;

final readonly class AdminStatistics
{
    public function __construct(
        public int $totalAdmins,
        public int $activeAdmins,
        public int $revokedAdmins,
        public int $adminsGrantedLast30Days,
        public int $adminsRevokedLast30Days
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            totalAdmins: (int) $row['total_admins'],
            activeAdmins: (int) $row['active_admins'],
            revokedAdmins: (int) $row['revoked_admins'],
            adminsGrantedLast30Days: (int) $row['admins_granted_last_30d'],
            adminsRevokedLast30Days: (int) $row['admins_revoked_last_30d'],
        );
    }
}
