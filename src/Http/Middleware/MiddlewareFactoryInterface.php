<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Http\MiddlewareOptions;
use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): MiddlewareInterface;
}