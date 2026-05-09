<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CloudflareHttpCacheBackendProvider implements HttpCacheBackendProvider
{
    public function supports(HttpCacheConfigurationInterface $configuration): bool
    {
        return $configuration instanceof CloudflareHttpCache;
    }

    public function context(HttpCacheConfigurationInterface $configuration): HttpCacheContext
    {
        return $this->configuration($configuration)->context;
    }

    public function read(HttpCacheConfigurationInterface $configuration, ResourcePolicyCollection $policies, ServerRequestInterface $request, HttpCacheRequestContext $context): ?ResponseInterface
    {
        return null;
    }

    public function write(HttpCacheConfigurationInterface $configuration, ResourcePolicyCollection $policies, ServerRequestInterface $request, HttpCacheRequestContext $context, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }

    public function invalidate(
        HttpCacheConfigurationInterface $configuration,
        ResourcePolicyCollection $policies,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $config = $this->configuration($configuration);
        $resources = $policies->resourceNames($request);

        return $config->context->withDebugHeaders($response, 'INVALIDATE', 'cloudflare')
            ->withHeader('X-Cache-Invalidate', HttpCacheHeader::cacheTags($resources))
            ->withHeader($config->tagHeader, HttpCacheHeader::cacheTags($resources));
    }

    public function buildHeaders(
        HttpCacheConfigurationInterface $configuration,
        HttpCachePolicy $policy,
        HttpCacheRequestContext $context,
        ResponseInterface $response
    ): ResponseInterface
    {
        $config = $this->configuration($configuration);
        if ($policy->mode === HttpCacheMode::PRIVATE) {
            return $response;
        }

        $response = $response
            ->withHeader($config->cdnCacheControlHeader, HttpCacheHeader::cacheControl($policy))
            ->withHeader($config->cloudflareCacheControlHeader, HttpCacheHeader::cacheControl($policy));

        if (!$policy->tags->isEmpty()) {
            $response = $response->withHeader($config->tagHeader, HttpCacheHeader::cacheTags($policy->tags));
        }

        return $config->context->withDebugHeaders($response, 'MISS', 'cloudflare');
    }

    private function configuration(HttpCacheConfigurationInterface $configuration): CloudflareHttpCache
    {
        if (!$configuration instanceof CloudflareHttpCache) {
            throw new \InvalidArgumentException('Cloudflare HTTP cache configuration expected');
        }

        return $configuration;
    }
}
