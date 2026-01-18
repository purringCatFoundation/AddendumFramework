<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Routing;

use Pradzikowski\Framework\Http\Routing\MiddlewareProviderInterface;
use Pradzikowski\Framework\Http\RouteMiddleware;
use Pradzikowski\Framework\Http\Middleware\Auth;
use Pradzikowski\Framework\Http\Middleware\RequestSignature;
use Pradzikowski\Game\Http\AccessControlMiddlewareProvider;
use Pradzikowski\Game\Http\RequestSignatureMiddlewareProvider;
use ReflectionClass;

class MiddlewareStackBuilder
{
    /** @var list<MiddlewareProviderInterface> */
    private array $providers = [];

    public function __construct()
    {
        $this->providers = [
            new ValidateRequestMiddlewareProvider(),
            new AccessControlMiddlewareProvider(),
            new CustomMiddlewareProvider(),
            // RequestSignatureMiddlewareProvider is game-specific, configured separately
            // new RequestSignatureMiddlewareProvider(),
        ];
    }

    /**
     * Builds the middleware stack for the given action class
     *
     * Special handling: RequestSignature middleware is automatically positioned:
     * - After Auth middleware if Auth is present
     * - At the beginning of the stack if Auth is not present
     *
     * @param ReflectionClass $actionClass
     * @return list<RouteMiddleware>
     */
    public function buildStack(ReflectionClass $actionClass): array
    {
        $middlewares = [];

        // Collect middlewares from all providers
        foreach ($this->providers as $provider) {
            $providedMiddlewares = $provider->provide($actionClass);
            $middlewares = array_merge($middlewares, $providedMiddlewares);
        }

        // Post-process: ensure RequestSignature is in the correct position
        $middlewares = $this->repositionRequestSignature($middlewares);

        return $middlewares;
    }

    /**
     * Repositions RequestSignature middleware to be after Auth if present,
     * or at the beginning if Auth is not present
     *
     * @param list<RouteMiddleware> $middlewares
     * @return list<RouteMiddleware>
     */
    private function repositionRequestSignature(array $middlewares): array
    {
        // Find and remove RequestSignature
        $requestSignatureMiddleware = null;
        $filteredMiddlewares = [];

        foreach ($middlewares as $middleware) {
            if ($middleware->getClass() === RequestSignature::class) {
                $requestSignatureMiddleware = $middleware;
            } else {
                $filteredMiddlewares[] = $middleware;
            }
        }

        // If no RequestSignature found, return original array
        if ($requestSignatureMiddleware === null) {
            return $middlewares;
        }

        // Find Auth position
        $authPosition = null;
        foreach ($filteredMiddlewares as $index => $middleware) {
            if ($middleware->getClass() === Auth::class) {
                $authPosition = $index;
                break;
            }
        }

        // Insert RequestSignature in the correct position
        if ($authPosition !== null) {
            // Insert after Auth
            array_splice(
                $filteredMiddlewares,
                $authPosition + 1,
                0,
                [$requestSignatureMiddleware]
            );
        } else {
            // Insert at the beginning
            array_unshift($filteredMiddlewares, $requestSignatureMiddleware);
        }

        return $filteredMiddlewares;
    }
}
