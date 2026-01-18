<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Attribute;

use Attribute;

/**
 * Command attribute for CLI commands
 *
 * Usage:
 * #[Command(name: 'app:hello', description: 'Outputs a greeting')]
 * class HelloCommand extends Command { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Command
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
    ) {
    }
}
