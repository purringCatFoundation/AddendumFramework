<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Config;

use RuntimeException;

class SystemEnvironmentProvider
{
    public function get(string $name, ?string $default = null): string
    {
        $value = $_ENV[$name] ?? getenv($name);
        if ($value === false) {
            if ($default === null) {
                throw new RuntimeException("Environment variable {$name} is required but not set");
            }
            return $default;
        }
        return $value;
    }
}