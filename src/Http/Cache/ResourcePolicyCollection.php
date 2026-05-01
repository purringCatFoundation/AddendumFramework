<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use PCF\Addendum\Attribute\ResourcePolicy;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ResourcePolicyCollection
{
    /**
     * @param non-empty-list<ResourcePolicy> $policies
     */
    public function __construct(
        private array $policies
    ) {
    }

    /**
     * @param list<ResourcePolicy> $policies
     */
    public static function fromArray(array $policies): ?self
    {
        if ($policies === []) {
            return new self([new ResourcePolicy()]);
        }

        return new self(array_values($policies));
    }

    public function primary(): ResourcePolicy
    {
        return $this->policies[0];
    }

    /**
     * @return list<ResourcePolicy>
     */
    public function all(): array
    {
        return $this->policies;
    }

    /**
     * @return list<string>
     */
    public function resourceNames(ServerRequestInterface $request): array
    {
        $resources = [];

        foreach ($this->policies as $policy) {
            $resource = trim($policy->resource);
            if ($resource === '') {
                continue;
            }

            $id = $policy->idAttribute !== null
                ? $this->requestValue($policy->idAttribute, $request)
                : null;

            if ($policy->idAttribute !== null && ($id === null || $id === '')) {
                continue;
            }

            $resources[] = $id !== null && $id !== ''
                ? $resource . ':' . (string) $id
                : $resource;
        }

        return array_values(array_unique($resources));
    }

    public function toHttpCachePolicy(ServerRequestInterface $request): HttpCachePolicy
    {
        $primary = $this->primary();
        $maxAge = $primary->maxAge > 0 ? $primary->maxAge : null;
        $resources = $this->resourceNames($request);

        return new HttpCachePolicy(
            mode: $primary->mode,
            maxAge: $maxAge,
            sharedMaxAge: $maxAge,
            staleWhileRevalidate: null,
            staleIfError: null,
            vary: [],
            tags: $resources,
            cacheErrors: $primary->cacheErrors,
            resources: $resources,
            invalidate: $resources
        );
    }

    private function requestValue(string $name, ServerRequestInterface $request): mixed
    {
        $value = $request->getAttribute($name);

        if ($value !== null) {
            return $value;
        }

        $routeParams = $request->getAttribute('route_params', []);
        if (is_array($routeParams) && array_key_exists($name, $routeParams)) {
            return $routeParams[$name];
        }

        $queryParams = $request->getQueryParams();
        if (array_key_exists($name, $queryParams)) {
            return $queryParams[$name];
        }

        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && array_key_exists($name, $parsedBody)) {
            return $parsedBody[$name];
        }

        return null;
    }
}
