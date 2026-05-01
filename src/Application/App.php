<?php
declare(strict_types=1);

namespace PCF\Addendum\Application;

use PCF\Addendum\Action\ActionRequestHandlerFactory;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use PCF\Addendum\Http\Cache\HttpCacheRuntime;
use PCF\Addendum\Http\Middleware\HttpCache;
use PCF\Addendum\Http\RouteMatch;
use PCF\Addendum\Http\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class App implements RequestHandlerInterface
{
    private const int JSON_FLAGS = JSON_THROW_ON_ERROR
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT;

    public function __construct(
        protected readonly Router $router,
        protected readonly LoggerInterface $logger,
        protected readonly HttpCacheRuntime $httpCacheRuntime
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $match = $this->router->match($request);
            if ($match === null) {
                $allowedMethods = $this->router->getAllowedMethodsForPath($request->getUri()->getPath());
                if ($allowedMethods !== []) {
                    $response = $this->jsonResponse(
                        ['error' => 'Method Not Allowed'],
                        405,
                        ['Allow' => implode(', ', $allowedMethods)]
                    );

                    return $this->withSecurityHeaders($response);
                }

                $response = $this->jsonResponse(['error' => 'Not found'], 404);

                return $this->withSecurityHeaders($response);
            }

            if (!$this->acceptsJson($request)) {
                return $this->withSecurityHeaders($this->jsonResponse(['error' => 'Not acceptable'], 406));
            }

            if (!$this->hasSupportedContentType($request)) {
                return $this->withSecurityHeaders($this->jsonResponse(['error' => 'Unsupported media type'], 415));
            }

            $handler = $this->routeHandler($match);
            $policies = $match->resourcePolicies;
            $response = new HttpCache($policies, $this->httpCacheRuntime)->process($match->request, $handler);

            return $this->withSecurityHeaders($response);
        } catch (\Throwable $e) {
            $this->logger->error('Unhandled exception in application', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $body = Utils::streamFor(json_encode([
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred'
            ], self::JSON_FLAGS));

            $response = new PsrResponse(
                status: 500,
                headers: ['Content-Type' => 'application/json'],
                body: $body
            );

            return $this->withSecurityHeaders($response);
        }
    }

    private function routeHandler(RouteMatch $match): RequestHandlerInterface
    {
        return new class($match, $this->logger) implements RequestHandlerInterface {
            public function __construct(
                private readonly RouteMatch $match,
                private readonly LoggerInterface $logger
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $handler = new ActionRequestHandlerFactory($this->logger)->create($this->match);

                return $handler->handle($request);
            }
        };
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    private function jsonResponse(array $payload, int $statusCode, array $headers = []): ResponseInterface
    {
        return new PsrResponse(
            status: $statusCode,
            headers: array_merge(['Content-Type' => 'application/json'], $headers),
            body: Utils::streamFor(json_encode($payload, self::JSON_FLAGS))
        );
    }

    private function acceptsJson(ServerRequestInterface $request): bool
    {
        $accept = trim($request->getHeaderLine('Accept'));
        if ($accept === '') {
            return true;
        }

        foreach (explode(',', $accept) as $acceptedType) {
            $mediaType = strtolower(trim(explode(';', $acceptedType)[0]));
            if (in_array($mediaType, ['*/*', 'application/*', 'application/json', 'application/problem+json'], true)) {
                return true;
            }

            if (str_ends_with($mediaType, '+json')) {
                return true;
            }
        }

        return false;
    }

    private function hasSupportedContentType(ServerRequestInterface $request): bool
    {
        if (!in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true)) {
            return true;
        }

        if (!$this->requestHasBody($request)) {
            return true;
        }

        $contentType = strtolower(trim(explode(';', $request->getHeaderLine('Content-Type'))[0]));

        return $contentType === 'application/json' || str_ends_with($contentType, '+json');
    }

    private function requestHasBody(ServerRequestInterface $request): bool
    {
        $body = $request->getBody();
        $size = $body->getSize();

        if ($size !== null) {
            return $size > 0;
        }

        $position = $body->isSeekable() ? $body->tell() : null;
        $contents = (string) $body;

        if ($position !== null) {
            $body->seek($position);
        }

        return trim($contents) !== '';
    }

    private function withSecurityHeaders(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-XSS-Protection', '1; mode=block')
            ->withHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'")
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withoutHeader('X-Powered-By')
            ->withoutHeader('Server');
    }
}
