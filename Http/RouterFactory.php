<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http;

use Pradzikowski\Framework\Http\Routing\ActionScanner;

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
