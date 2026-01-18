<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Routing;

use Pradzikowski\Framework\Attribute\Route;

class RoutePatternCompiler
{
    /**
     * Compiles a route pattern to a regex pattern
     *
     * Converts route parameters like :userId to named regex groups (?P<userId>...)
     * Uses requirements from the route if specified, otherwise defaults to [^/]+
     *
     * @param Route $route
     * @return string Regex pattern ready for preg_match
     */
    public function compile(Route $route): string
    {
        $pattern = preg_replace_callback(
            '#:([A-Za-z_][A-Za-z0-9_-]*)#',
            function ($matches) use ($route) {
                $paramName = $matches[1];
                $requirement = $route->requirements[$paramName] ?? '[^/]+';
                return '(?P<' . $paramName . '>' . $requirement . ')';
            },
            $route->path
        );

        return '#^' . $pattern . '$#';
    }
}
