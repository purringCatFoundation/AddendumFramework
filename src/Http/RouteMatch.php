<?php
declare(strict_types=1);

namespace PCF\Addendum\Http;

use Psr\Http\Message\ServerRequestInterface;

class RouteMatch
{
    /**
     * @param list<RouteMiddleware> $middlewares
     */
    public function __construct(
        public readonly string $actionClass,
        public readonly array $middlewares,
        public readonly ServerRequestInterface $request
    ) {
    }

}