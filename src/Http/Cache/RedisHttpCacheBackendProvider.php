<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RedisHttpCacheBackendProvider implements HttpCacheBackendProvider
{
    private readonly HttpCacheKeyGenerator $keyGenerator;

    public function __construct(
        private HttpResponseCache $cache,
        ?HttpCacheKeyGenerator $keyGenerator = null
    ) {
        $this->keyGenerator = $keyGenerator ?? new HttpCacheKeyGenerator();
    }

    public function supports(HttpCacheConfigurationInterface $configuration): bool
    {
        return $configuration instanceof RedisHttpCache;
    }

    public function context(HttpCacheConfigurationInterface $configuration): HttpCacheContext
    {
        return $this->configuration($configuration)->context;
    }

    public function read(
        HttpCacheConfigurationInterface $configuration,
        ResourcePolicyCollection $policies,
        ServerRequestInterface $request,
        HttpCacheRequestContext $context
    ): ?ResponseInterface {
        $config = $this->configuration($configuration);
        $policy = $policies->toHttpCachePolicy($request);
        $response = $this->cache->get($this->keyGenerator->generate($config->keyPrefix, $policy, $request, $context));

        return $response !== null ? $this->withCacheState($config, $response, 'HIT') : null;
    }

    public function write(
        HttpCacheConfigurationInterface $configuration,
        ResourcePolicyCollection $policies,
        ServerRequestInterface $request,
        HttpCacheRequestContext $context,
        ResponseInterface $response
    ): ResponseInterface {
        $config = $this->configuration($configuration);
        $policy = $policies->toHttpCachePolicy($request);
        $ttl = $policy->redisTtl();

        if ($ttl === null) {
            return $response;
        }

        $this->cache->set(
            $this->keyGenerator->generate($config->keyPrefix, $policy, $request, $context),
            $response,
            $ttl,
            $policies->resourceNames($request)
        );

        return $this->withCacheState($config, $response, 'MISS');
    }

    public function invalidate(
        HttpCacheConfigurationInterface $configuration,
        ResourcePolicyCollection $policies,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $config = $this->configuration($configuration);
        $resources = $policies->resourceNames($request);
        $this->cache->invalidate($resources);

        return $config->context->withDebugHeaders(
            $response->withHeader('X-Cache-Invalidate', HttpCacheHeader::cacheTags($resources)),
            'INVALIDATE',
            'redis'
        );
    }

    public function buildHeaders(
        HttpCacheConfigurationInterface $configuration,
        HttpCachePolicy $policy,
        HttpCacheRequestContext $context,
        ResponseInterface $response
    ): ResponseInterface {
        $this->configuration($configuration);

        return $response;
    }

    private function configuration(HttpCacheConfigurationInterface $configuration): RedisHttpCache
    {
        if (!$configuration instanceof RedisHttpCache) {
            throw new \InvalidArgumentException('Redis HTTP cache configuration expected');
        }

        return $configuration;
    }

    private function withCacheState(RedisHttpCache $configuration, ResponseInterface $response, string $state): ResponseInterface
    {
        if (!$configuration->context->debugHeaders) {
            return $response;
        }

        return $configuration->context
            ->withDebugHeaders($response, $state, 'redis')
            ->withHeader($configuration->hitHeader, $state);
    }

}
