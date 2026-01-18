<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Middleware;

use Pradzikowski\Framework\Http\MiddlewareOptions;
use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): MiddlewareInterface;
}