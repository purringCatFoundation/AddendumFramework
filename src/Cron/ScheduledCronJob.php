<?php
declare(strict_types=1);

namespace PCF\Addendum\Cron;

final readonly class ScheduledCronJob
{
    public function __construct(
        public int $id,
        public string $code
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            code: $row['code'],
        );
    }
}
