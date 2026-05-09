<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Http\RegisteredRoute;

final readonly class CacheDebugRouteRow
{
    public function __construct(
        public string $method,
        public RegisteredRoute $route
    ) {
    }
}
