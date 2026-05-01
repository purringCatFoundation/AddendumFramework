<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use BackedEnum;
use PCF\Addendum\Http\Cache\HttpCacheMode;
use PCF\Addendum\Http\Cache\HttpCachePolicy;
use PCF\Addendum\Http\Cache\HttpCacheRequestContext;
use PCF\Addendum\Http\Cache\HttpCacheRuntime;
use PCF\Addendum\Http\Cache\HttpCacheHeader;
use PCF\Addendum\Http\Cache\ResourcePolicyCollection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class HttpCache implements MiddlewareInterface
{
    private const array CACHEABLE_METHODS = ['GET', 'HEAD', 'OPTIONS'];
    private const array INVALIDATING_METHODS = ['POST', 'PUT', 'DELETE', 'PATCH'];

    public function __construct(
        private ResourcePolicyCollection $policies,
        private HttpCacheRuntime $runtime
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $context = $this->createRequestContext($request);

        if ($this->isInvalidatingMethod($request)) {
            return $this->processInvalidation($request, $handler);
        }

        if ($this->canRead($request, $context)) {
            $cachedResponse = $this->runtime->backendProvider->read(
                $this->runtime->configuration,
                $this->policies,
                $request,
                $context
            );

            if ($cachedResponse !== null) {
                return $cachedResponse;
            }

            $response = $handler->handle($request);
            $policy = $this->shouldForcePrivate($request, $response, $context)
                ? HttpCachePolicy::privateNoStore()
                : $this->policies->toHttpCachePolicy($request);
            $response = $this->applyPolicy($response, $policy, $context);

            if ($policy->mode !== HttpCacheMode::PRIVATE && $policy->redisTtl() !== null) {
                $response = $this->runtime->backendProvider->write(
                    $this->runtime->configuration,
                    $this->policies,
                    $request,
                    $context,
                    $response
                );
            }

            return $response;
        }

        $response = $handler->handle($request);
        $policy = $this->shouldForcePrivate($request, $response, $context)
            ? HttpCachePolicy::privateNoStore()
            : $this->policies->toHttpCachePolicy($request);

        return $this->applyPolicy($response, $policy, $context);
    }

    private function processInvalidation(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $this->withoutProxyHeaders(
            $handler->handle($request)->withHeader('Cache-Control', 'private, no-store')
        );

        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        $resources = $this->policies->resourceNames($request);

        if ($resources === []) {
            return $response;
        }

        return $this->runtime->backendProvider->invalidate(
            $this->runtime->configuration,
            $this->policies,
            $request,
            $response
        );
    }

    private function shouldForcePrivate(
        ServerRequestInterface $request,
        ResponseInterface $response,
        HttpCacheRequestContext $context
    ): bool {
        if (!$this->isCacheableMethod($request)) {
            return true;
        }

        if (!$this->policies->primary()->cacheErrors && $response->getStatusCode() >= 400) {
            return true;
        }

        return $this->policies->primary()->mode === HttpCacheMode::USER_AWARE
            && (!$context->authenticated || !$context->trustedUserContext);
    }

    private function canRead(ServerRequestInterface $request, HttpCacheRequestContext $context): bool
    {
        $policy = $this->policies->toHttpCachePolicy($request);

        if (!$this->isCacheableMethod($request) || $policy->redisTtl() === null) {
            return false;
        }

        if ($policy->mode === HttpCacheMode::PRIVATE) {
            return false;
        }

        return $policy->mode !== HttpCacheMode::USER_AWARE
            || ($context->authenticated && $context->trustedUserContext);
    }

    private function isCacheableMethod(ServerRequestInterface $request): bool
    {
        return in_array(strtoupper($request->getMethod()), self::CACHEABLE_METHODS, true);
    }

    private function isInvalidatingMethod(ServerRequestInterface $request): bool
    {
        return in_array(strtoupper($request->getMethod()), self::INVALIDATING_METHODS, true);
    }

    private function applyPolicy(
        ResponseInterface $response,
        HttpCachePolicy $policy,
        HttpCacheRequestContext $context
    ): ResponseInterface {
        $response = $response->withHeader('Cache-Control', HttpCacheHeader::cacheControl($policy));

        if ($policy->mode === HttpCacheMode::PRIVATE) {
            return $this->withoutProxyHeaders($response);
        }

        $vary = $this->responseVary($response);

        foreach ($policy->vary as $header) {
            $vary[] = $header;
        }

        if ($policy->mode === HttpCacheMode::GUEST_AWARE) {
            $authStateHeader = $this->runtime->backendProvider->context($this->runtime->configuration)->authStateHeader;
            $vary[] = $authStateHeader;
            $response = $response->withHeader($authStateHeader, $context->authState);
        }

        if ($policy->mode === HttpCacheMode::USER_AWARE) {
            $vary[] = $this->runtime->backendProvider->context($this->runtime->configuration)->userContextHeader;
        }

        $varyHeader = HttpCacheHeader::headerList($vary);
        if ($varyHeader !== '') {
            $response = $response->withHeader('Vary', $varyHeader);
        }

        return $this->runtime->backendProvider->buildHeaders(
            $this->runtime->configuration,
            $policy,
            $context,
            $response
        );
    }

    private function withoutProxyHeaders(ResponseInterface $response): ResponseInterface
    {
        foreach ([
            'Surrogate-Control',
            'Surrogate-Key',
            'X-Accel-Expires',
            'X-Cache-Tags',
            'Souin-Cache-Tags',
            'Cache-Tag',
            'CDN-Cache-Control',
            'Cloudflare-CDN-Cache-Control',
        ] as $header) {
            $response = $response->withoutHeader($header);
        }

        return $response;
    }

    /**
     * @return list<string>
     */
    private function responseVary(ResponseInterface $response): array
    {
        $vary = [];

        foreach ($response->getHeader('Vary') as $line) {
            foreach (explode(',', $line) as $header) {
                $vary[] = trim($header);
            }
        }

        return $vary;
    }

    private function createRequestContext(ServerRequestInterface $request): HttpCacheRequestContext
    {
        $userUuid = $request->getAttribute('user_uuid');
        $userUuid = $userUuid !== null ? (string) $userUuid : null;
        $tokenType = $this->tokenType($request->getAttribute('token_type'));
        $cacheContext = $this->runtime->backendProvider->context($this->runtime->configuration);
        $userContextHash = trim($request->getHeaderLine($cacheContext->userContextHeader));
        $userContextSignature = trim($request->getHeaderLine($cacheContext->userContextSignatureHeader));
        $trustedUserContext = false;

        if ($userUuid !== null && $userContextHash !== '' && $userContextSignature !== '') {
            $trustedUserContext = $cacheContext->isTrustedUserContext(
                $userContextHash,
                $userContextSignature,
                $userUuid,
                $tokenType
            );
        }

        return new HttpCacheRequestContext(
            authenticated: $userUuid !== null,
            userUuid: $userUuid,
            tokenType: $tokenType,
            authState: $userUuid !== null ? 'authenticated' : 'guest',
            userContextHash: $userContextHash !== '' ? $userContextHash : null,
            trustedUserContext: $trustedUserContext
        );
    }

    private function tokenType(mixed $tokenType): ?string
    {
        if ($tokenType instanceof BackedEnum) {
            return (string) $tokenType->value;
        }

        return $tokenType !== null ? (string) $tokenType : null;
    }
}
