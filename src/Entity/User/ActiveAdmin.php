<?php
declare(strict_types=1);

namespace PCF\Addendum\Entity\User;

use DateTimeImmutable;

final readonly class ActiveAdmin
{
    public function __construct(
        public string $adminUuid,
        public string $userEmail,
        public DateTimeImmutable $grantedAt,
        public ?string $grantedByEmail = null,
        public ?string $grantedReason = null
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            adminUuid: $row['admin_uuid'],
            userEmail: $row['user_email'],
            grantedAt: new DateTimeImmutable($row['granted_at']),
            grantedByEmail: $row['granted_by_email'] ?? null,
            grantedReason: $row['granted_reason'] ?? null,
        );
    }
}
