<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Middleware;

use Pradzikowski\Framework\Http\Middleware\MiddlewareFactoryInterface;
use Pradzikowski\Framework\Http\MiddlewareOptions;

class ValidateRequestAttributeFactory implements MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): ValidateRequestAttribute
    {
        return new ValidateRequestAttribute($options->actionClass);
    }
}