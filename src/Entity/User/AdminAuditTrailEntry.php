<?php
declare(strict_types=1);

namespace PCF\Addendum\Entity\User;

use DateTimeImmutable;

final readonly class AdminAuditTrailEntry
{
    public function __construct(
        public string $adminUuid,
        public string $action,
        public DateTimeImmutable $actionAt,
        public ?string $actionByEmail = null,
        public ?string $reason = null
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            adminUuid: $row['admin_uuid'],
            action: $row['action'],
            actionAt: new DateTimeImmutable($row['action_at']),
            actionByEmail: $row['action_by_email'] ?? null,
            reason: $row['reason'] ?? null,
        );
    }
}
