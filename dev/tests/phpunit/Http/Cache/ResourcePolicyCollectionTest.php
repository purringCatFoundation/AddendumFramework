<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http\Cache;

use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Attribute\ResourcePolicy;
use PCF\Addendum\Http\Cache\HttpCacheMode;
use PCF\Addendum\Http\Cache\ResourcePolicyCollection;
use PCF\Addendum\Http\RouteParameters;
use PHPUnit\Framework\TestCase;

final class ResourcePolicyCollectionTest extends TestCase
{
    public function testResourceNamesReadIdsFromRequestLocationsAndDeduplicate(): void
    {
        $collection = new ResourcePolicyCollection([
            new ResourcePolicy(resource: 'article', idAttribute: 'articleUuid'),
            new ResourcePolicy(resource: 'article', idAttribute: 'articleUuid'),
            new ResourcePolicy(resource: 'route', idAttribute: 'routeUuid'),
            new ResourcePolicy(resource: 'query', idAttribute: 'queryUuid'),
            new ResourcePolicy(resource: 'body', idAttribute: 'bodyUuid'),
            new ResourcePolicy(resource: 'feed'),
            new ResourcePolicy(resource: 'missing', idAttribute: 'missingUuid'),
            new ResourcePolicy(resource: ' '),
        ]);
        $request = (new ServerRequest('GET', '/articles'))
            ->withAttribute('articleUuid', 'article-1')
            ->withAttribute('route_params', new RouteParameters(['routeUuid' => 'route-1']))
            ->withQueryParams(['queryUuid' => 'query-1'])
            ->withParsedBody(['bodyUuid' => 'body-1']);

        self::assertSame(
            ['article:article-1', 'route:route-1', 'query:query-1', 'body:body-1', 'feed'],
            $collection->resourceNames($request)->toArray()
        );
    }

    public function testToHttpCachePolicyUsesPrimaryPolicyAndResolvedResources(): void
    {
        $collection = new ResourcePolicyCollection([
            new ResourcePolicy(mode: HttpCacheMode::GUEST_AWARE, maxAge: 30, resource: 'article', idAttribute: 'articleUuid', cacheErrors: true),
            new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 60, resource: 'feed'),
        ]);
        $request = (new ServerRequest('GET', '/articles/1'))->withAttribute('articleUuid', 'article-1');

        $policy = $collection->toHttpCachePolicy($request);

        self::assertSame(HttpCacheMode::GUEST_AWARE, $policy->mode);
        self::assertSame(30, $policy->maxAge);
        self::assertSame(30, $policy->sharedMaxAge);
        self::assertTrue($policy->cacheErrors);
        self::assertSame(['article:article-1', 'feed'], $policy->tags->toArray());
        self::assertSame(['article:article-1', 'feed'], $policy->invalidate->toArray());
    }
}
