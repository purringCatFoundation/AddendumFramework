<?php

declare(strict_types=1);

namespace PCF\Addendum\Action;

use PCF\Addendum\Action\ActionFactoryInterface;
use PCF\Addendum\Http\Middleware\MiddlewareFactoryInterface;
use PCF\Addendum\Http\MiddlewareRequestHandlerFactory;
use PCF\Addendum\Http\RouteMatch;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class ActionRequestHandlerFactory
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function create(RouteMatch $match): RequestHandlerInterface
    {
        /** @var class-string<ActionFactoryInterface> $factoryClass */
        $factoryClass = $match->actionClass . 'Factory';
        $action = new $factoryClass()->create();
        $handler = new ActionRequestHandler($action, $this->logger);

        foreach ($match->middlewares->reversed() as $middlewareRoute) {
            /** @var class-string<MiddlewareFactoryInterface> $middlewareFactory */
            $middlewareFactory = $middlewareRoute->getClass() . 'Factory';
            $middleware = new $middlewareFactory()->create($middlewareRoute->getOptions());
            $handler = new MiddlewareRequestHandlerFactory()->create($middleware, $handler);
        }

        return $handler;
    }
}
