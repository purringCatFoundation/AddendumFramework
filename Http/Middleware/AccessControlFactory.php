<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Middleware;

use Pradzikowski\Framework\Http\Middleware\AccessControl;
use Pradzikowski\Framework\Http\Middleware\MiddlewareFactoryInterface;
use Pradzikowski\Framework\Http\MiddlewareOptions;
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
