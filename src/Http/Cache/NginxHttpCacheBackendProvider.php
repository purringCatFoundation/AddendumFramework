<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class NginxHttpCacheBackendProvider implements HttpCacheBackendProvider
{
    public function supports(HttpCacheConfigurationInterface $configuration): bool
    {
        return $configuration instanceof NginxHttpCache;
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

        return $config->context->withDebugHeaders($response, 'INVALIDATE', 'nginx')
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

        if ($config->emitAccelExpires && $policy->sharedMaxAge !== null) {
            $response = $response->withHeader($config->accelExpiresHeader, (string) $policy->sharedMaxAge);
        }
        if (!$policy->tags->isEmpty()) {
            $response = $response->withHeader($config->tagHeader, HttpCacheHeader::cacheTags($policy->tags));
        }

        return $config->context->withDebugHeaders($response, 'MISS', 'nginx');
    }

    private function configuration(HttpCacheConfigurationInterface $configuration): NginxHttpCache
    {
        if (!$configuration instanceof NginxHttpCache) {
            throw new \InvalidArgumentException('Nginx HTTP cache configuration expected');
        }

        return $configuration;
    }
}
