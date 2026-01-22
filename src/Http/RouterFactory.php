<?php
declare(strict_types=1);

namespace PCF\Addendum\Http;

use PCF\Addendum\Http\Routing\ActionScanner;

class RouterFactory
{
    /**
     * @param ActionScanner[] $scanners
     */
    public function __construct(
        private readonly array $scanners
    ) {
    }

    public function create(): Router
    {
        return new Router($this->scanners);
    }
}
