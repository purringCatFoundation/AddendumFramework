<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CaddyHttpCacheBackendProvider implements HttpCacheBackendProvider
{
    public function supports(HttpCacheConfigurationInterface $configuration): bool
    {
        return $configuration instanceof CaddyHttpCache;
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
        $tagHeader = $config->cacheHandler === CaddyHttpCache::SOUIN ? $config->souinTagHeader : $config->tagHeader;

        return $config->context->withDebugHeaders($response, 'INVALIDATE', 'caddy')
            ->withHeader('X-Cache-Invalidate', HttpCacheHeader::cacheTags($resources))
            ->withHeader($tagHeader, HttpCacheHeader::cacheTags($resources));
    }

    public function buildHeaders(
        HttpCacheConfigurationInterface $configuration,
        HttpCachePolicy $policy,
        HttpCacheRequestContext $context,
        ResponseInterface $response
    ): ResponseInterface
    {
        $config = $this->configuration($configuration);
        if ($policy->mode === HttpCacheMode::PRIVATE || $policy->tags === []) {
            return $response;
        }

        $header = $config->cacheHandler === CaddyHttpCache::SOUIN ? $config->souinTagHeader : $config->tagHeader;

        return $config->context->withDebugHeaders(
            $response->withHeader($header, HttpCacheHeader::cacheTags($policy->tags)),
            'MISS',
            'caddy'
        );
    }

    private function configuration(HttpCacheConfigurationInterface $configuration): CaddyHttpCache
    {
        if (!$configuration instanceof CaddyHttpCache) {
            throw new \InvalidArgumentException('Caddy HTTP cache configuration expected');
        }

        return $configuration;
    }
}
