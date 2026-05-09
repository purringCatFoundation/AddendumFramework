<?php
declare(strict_types=1);

namespace PCF\Addendum\Http;

use PCF\Addendum\Http\Cache\ResourcePolicyCollection;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class RegisteredRoute
{
    public readonly string $path;
    public readonly RouteMiddlewareCollection $middlewares;

    public function __construct(
        public readonly string $pattern,
        public readonly string $actionClass,
        iterable $middlewares,
        public readonly ResourcePolicyCollection $resourcePolicies,
        ?string $path = null
    ) {
        $this->middlewares = $middlewares instanceof RouteMiddlewareCollection
            ? $middlewares
            : new RouteMiddlewareCollection($middlewares);
        $this->path = $path ?? self::pathFromPattern($pattern);
    }

    private static function pathFromPattern(string $pattern): string
    {
        if (!str_starts_with($pattern, '#^') || !str_ends_with($pattern, '$#')) {
            return $pattern;
        }

        $path = substr($pattern, 2, -2);
        $path = preg_replace('#\(\?P<([A-Za-z_][A-Za-z0-9_-]*)>[^)]+\)#', ':$1', $path) ?? $path;

        return str_replace('\\/', '/', $path);
    }


    public function matches(string $path): ?RouteParameters
    {
        if (preg_match($this->pattern, $path, $matches)) {
            return RouteParameters::fromRegexMatches($matches);
        }

        return null;
    }

    public function createMatchResult(ServerRequestInterface $request): RouteMatch
    {
        $matches = $this->matches($request->getUri()->getPath());
        if ($matches === null) {
            throw new RuntimeException('Route does not match the request path');
        }

        $routeParams = $matches;

        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $request = $request->withAttribute($key, $value);
            }
        }

        $request = $request
            ->withAttribute('action_class', $this->actionClass)
            ->withAttribute('route_params', $routeParams);

        return new RouteMatch(
            $this->actionClass,
            $this->middlewares,
            $request,
            $this->resourcePolicies
        );
    }
}
