<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

final readonly class DatabaseTestFailure
{
    public function __construct(
        public string $name,
        public string $output
    ) {
    }
}
