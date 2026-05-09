<?php
declare(strict_types=1);

namespace PCF\Addendum\Cron;

final readonly class CronDefinition
{
    public function __construct(
        public string $code,
        public string $expression,
        public bool $enabled
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            code: $row['code'],
            expression: $row['expression'],
            enabled: (bool) $row['enabled'],
        );
    }
}
