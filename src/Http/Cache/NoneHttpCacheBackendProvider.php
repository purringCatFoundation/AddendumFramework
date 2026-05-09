<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class NoneHttpCacheBackendProvider implements HttpCacheBackendProvider
{
    public function supports(HttpCacheConfigurationInterface $configuration): bool
    {
        return $configuration instanceof NoneHttpCache;
    }

    public function context(HttpCacheConfigurationInterface $configuration): HttpCacheContext
    {
        return $configuration instanceof NoneHttpCache ? $configuration->context : new HttpCacheContext();
    }

    public function read(
        HttpCacheConfigurationInterface $configuration,
        ResourcePolicyCollection $policies,
        ServerRequestInterface $request,
        HttpCacheRequestContext $context
    ): ?ResponseInterface {
        return null;
    }

    public function write(
        HttpCacheConfigurationInterface $configuration,
        ResourcePolicyCollection $policies,
        ServerRequestInterface $request,
        HttpCacheRequestContext $context,
        ResponseInterface $response
    ): ResponseInterface {
        return $response;
    }

    public function invalidate(
        HttpCacheConfigurationInterface $configuration,
        ResourcePolicyCollection $policies,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        return $response;
    }

    public function buildHeaders(
        HttpCacheConfigurationInterface $configuration,
        HttpCachePolicy $policy,
        HttpCacheRequestContext $context,
        ResponseInterface $response
    ): ResponseInterface {
        return $response;
    }
}
