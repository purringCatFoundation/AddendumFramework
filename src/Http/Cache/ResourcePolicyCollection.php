<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use Ds\Vector;
use PCF\Addendum\Attribute\ResourcePolicy;
use PCF\Addendum\Http\RouteParameters;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ResourcePolicyCollection
{
    /** @var Vector<ResourcePolicy> */
    private Vector $policies;

    /** @param iterable<ResourcePolicy> $policies */
    public function __construct(iterable $policies)
    {
        $this->policies = $policies instanceof Vector ? $policies->copy() : new Vector($policies);
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
        return $this->policies->first();
    }

    /** @return Vector<ResourcePolicy> */
    public function all(): Vector
    {
        return $this->policies->copy();
    }

    /** @return Vector<string> */
    public function resourceNames(ServerRequestInterface $request): Vector
    {
        $resources = new Vector();

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

            $name = $id !== null && $id !== ''
                ? $resource . ':' . (string) $id
                : $resource;

            if (!$resources->contains($name)) {
                $resources->push($name);
            }
        }

        return $resources;
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

        $routeParams = $request->getAttribute('route_params');
        if ($routeParams instanceof RouteParameters) {
            $routeValue = $routeParams->get($name);

            if ($routeValue !== null) {
                return $routeValue;
            }
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
