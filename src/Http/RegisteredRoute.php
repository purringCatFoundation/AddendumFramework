<?php
declare(strict_types=1);

namespace PCF\Addendum\Http;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class RegisteredRoute
{
    /**
     * @param list<RouteMiddleware> $middlewares
     */
    public function __construct(
        public readonly string $pattern,
        public readonly string $actionClass,
        public readonly array $middlewares = []
    ) {
    }


    public function matches(string $path): ?array
    {
        if (preg_match($this->pattern, $path, $matches)) {
            return $matches;
        }
        return null;
    }

    public function createMatchResult(ServerRequestInterface $request): RouteMatch
    {
        $matches = $this->matches($request->getUri()->getPath());
        if ($matches === null) {
            throw new RuntimeException('Route does not match the request path');
        }

        foreach ($matches as $key => $value) {
            if (!is_int($key)) {
                $request = $request->withAttribute($key, $value);
            }
        }

        $middlewaresWithActionClass = array_map(
            fn(RouteMiddleware $middleware) => $middleware->withActionClass($this->actionClass),
            $this->middlewares
        );

        return new RouteMatch(
            $this->actionClass,
            $middlewaresWithActionClass,
            $request
        );
    }
}