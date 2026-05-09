<?php
declare(strict_types=1);

namespace PCF\Addendum\Cron;

final readonly class SchedulableCron
{
    /** @param class-string<CronInterface> $className */
    public function __construct(
        public string $code,
        public string $className,
        public string $expression,
        public bool $enabled
    ) {
    }
}
