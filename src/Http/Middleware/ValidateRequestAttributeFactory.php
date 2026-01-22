<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Http\Middleware\MiddlewareFactoryInterface;
use PCF\Addendum\Http\MiddlewareOptions;

class ValidateRequestAttributeFactory implements MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): ValidateRequestAttribute
    {
        return new ValidateRequestAttribute($options->actionClass);
    }
}