<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Middleware;

use Pradzikowski\Framework\Http\Middleware\MiddlewareFactoryInterface;
use Pradzikowski\Framework\Http\MiddlewareOptions;

class DummyFactory implements MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): Dummy
    {
        return new Dummy($options->toArray());
    }
}
