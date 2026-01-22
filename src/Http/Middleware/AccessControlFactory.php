<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Http\Middleware\AccessControl;
use PCF\Addendum\Http\Middleware\MiddlewareFactoryInterface;
use PCF\Addendum\Http\MiddlewareOptions;
use Psr\Container\ContainerInterface;

class AccessControlFactory implements MiddlewareFactoryInterface
{
    public function __construct(
        private ?ContainerInterface $container = null
    ) {
    }

    /**
     * Create AccessControl middleware
     *
     * @param MiddlewareOptions $options Middleware options containing actionClass
     */
    public function create(MiddlewareOptions $options): AccessControl
    {
        return new AccessControl($this->container, $options->actionClass);
    }
}
