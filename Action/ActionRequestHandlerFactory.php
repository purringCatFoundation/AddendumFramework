<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action;

use Pradzikowski\Framework\Action\ActionFactoryInterface;
use Pradzikowski\Framework\Http\Middleware\MiddlewareFactoryInterface;
use Pradzikowski\Framework\Http\MiddlewareRequestHandlerFactory;
use Pradzikowski\Framework\Http\RouteMatch;
use Psr\Http\Server\MiddlewareInterface;
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
        /** @var ActionFactoryInterface $factoryClass */
        $factoryClass = $match->actionClass . 'Factory';
        $action = new $factoryClass()->create();
        $handler = new ActionRequestHandler($action, $this->logger);
        
        foreach (array_reverse($match->middlewares) as $middlewareRoute) {
            /** @var MiddlewareFactoryInterface $middlewareFactory */
            $middlewareFactory = $middlewareRoute->getClass() . 'Factory';
            /** @var MiddlewareInterface $middleware */
            $middleware = new $middlewareFactory()->create($middlewareRoute->getOptions());
            $handler = new MiddlewareRequestHandlerFactory()->create($middleware, $handler);
        }

        return $handler;
    }
}
