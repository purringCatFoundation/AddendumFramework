<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Auth\Jwt;
use PCF\Addendum\Auth\Session;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Auth\TokenValidationRepository;
use PCF\Addendum\Repository\User\ApplicationTokenRepository;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Auth implements MiddlewareInterface
{
    public function __construct(
        private readonly string                             $secret,
        private readonly TokenValidationRepository $tokenValidationRepository,
        private readonly ApplicationTokenRepository $applicationTokenRepository
    )
    {
    }

    /**
     * Authenticate JWT token and add user attributes to request
     *
     * Handles two types of tokens:
     * 1. User/Character tokens - validated against user token cache
     * 2. Application tokens - validated against application_tokens table
     *
     * @param ServerRequestInterface $request HTTP request with Authorization header
     * @param RequestHandlerInterface $handler Next request handler
     * @return ResponseInterface HTTP response or 401 if authentication fails
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');
        if (!preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $this->createUnauthorizedResponse('Missing or invalid authorization header');
        }

        $token = $matches[1];

        try {
            $payload = Jwt::decode($token, $this->secret);

            // Check if this is an application token
            if ($payload->tokenType === TokenType::APPLICATION) {
                // Validate application token against database
                if (!$this->applicationTokenRepository->isTokenValid($payload->jti)) {
                    return $this->createUnauthorizedResponse('Application token has been revoked');
                }

                // Update last used timestamp
                $this->applicationTokenRepository->updateLastUsed($payload->jti);
            } else {
                // Validate user/character token against token cache
                if (!$this->tokenValidationRepository->isTokenValid($payload->sub, $payload->iat)) {
                    return $this->createUnauthorizedResponse('Token has been revoked');
                }
            }

            // Create session from token payload
            $session = Session::fromTokenPayload($payload);

            $request = $request
                ->withAttribute('user_uuid', $payload->sub)
                ->withAttribute('jti', $payload->jti)
                ->withAttribute('fingerprint_hash', $payload->fingerprintHash)
                ->withAttribute('token_issued_at', $payload->iat)
                ->withAttribute('token_expires_at', $payload->exp)
                ->withAttribute('token_payload', $payload)
                ->withAttribute('token_type', $payload->getTokenType())
                ->withAttribute('session', $session);

        } catch (\Throwable $e) {
            return $this->createUnauthorizedResponse('Invalid token');
        }

        return $handler->handle($request);
    }

    private function createUnauthorizedResponse(string $message): ResponseInterface
    {
        $body = Utils::streamFor(json_encode([
            'error' => 'Unauthorized',
            'message' => $message
        ]));

        return new PsrResponse(
            status: 401,
            headers: ['Content-Type' => 'application/json'],
            body: $body
        );
    }
}