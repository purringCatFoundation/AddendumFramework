<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use Symfony\Component\Console\Command\Command;

final readonly class CommandDefinition
{
    /**
     * @param class-string<Command> $class
     * @param class-string|null $factory
     */
    public function __construct(
        public string $name,
        public string $description,
        public string $class,
        public ?string $factory = null
    ) {
    }
}
