<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Security Headers middleware
 *
 * Adds security headers to all responses:
 * - X-Content-Type-Options: nosniff
 * - X-Frame-Options: DENY
 * - X-XSS-Protection: 1; mode=block
 * - Content-Security-Policy: restrictive policy for API
 * - Referrer-Policy: strict-origin-when-cross-origin
 * - Removes X-Powered-By header
 *
 * This should be registered as a global middleware.
 */
class SecurityHeaders implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response
            // Prevent MIME type sniffing
            ->withHeader('X-Content-Type-Options', 'nosniff')

            // Prevent clickjacking
            ->withHeader('X-Frame-Options', 'DENY')

            // Enable XSS protection (legacy header, but still useful)
            ->withHeader('X-XSS-Protection', '1; mode=block')

            // Restrictive CSP for API (no scripts, no frames)
            ->withHeader(
                'Content-Security-Policy',
                "default-src 'none'; frame-ancestors 'none'"
            )

            // Control referer information
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')

            // Remove server information leakage
            ->withoutHeader('X-Powered-By')
            ->withoutHeader('Server');
    }
}
