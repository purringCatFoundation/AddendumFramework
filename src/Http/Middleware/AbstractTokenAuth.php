<?php

declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PCF\Addendum\Auth\Jwt;
use PCF\Addendum\Auth\Session;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenValidationRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

abstract class AbstractTokenAuth implements MiddlewareInterface
{
    public function __construct(
        private readonly string $publicKeyPath,
        private readonly TokenValidationRepository $tokenValidationRepository
    ) {
    }

    abstract protected function tokenError(TokenPayload $payload, string $token): ?string;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!preg_match('/^Bearer\s+(\S+)$/i', $request->getHeaderLine('Authorization'), $matches)) {
            return $this->unauthorized('Missing or invalid authorization header');
        }

        $token = $matches[1];
        try {
            $payload = Jwt::decode($token, $this->publicKeyPath);
            $error = $this->tokenError($payload, $token);
            if ($error !== null) {
                return $this->unauthorized($error);
            }

            if (
                !$this->tokenValidationRepository->isTokenValid(
                    $payload->getTokenType(),
                    $payload->sub,
                    $payload->jti,
                    $payload->iat,
                    $payload->sid
                )
            ) {
                return $this->unauthorized('Token has been revoked');
            }

            $request = $request
                ->withAttribute('user_uuid', $payload->sub)
                ->withAttribute('jti', $payload->jti)
                ->withAttribute('session_id', $payload->sid)
                ->withAttribute('fingerprint_hash', $payload->fingerprintHash)
                ->withAttribute('token_issued_at', $payload->iat)
                ->withAttribute('token_expires_at', $payload->exp)
                ->withAttribute('token_payload', $payload)
                ->withAttribute('token_type', $payload->getTokenType())
                ->withAttribute('jwt_token', $token)
                ->withAttribute('session', Session::fromTokenPayload($payload));
        } catch (Throwable) {
            return $this->unauthorized('Invalid token');
        }

        return $handler->handle($request);
    }

    private function unauthorized(string $message): ResponseInterface
    {
        return new Response(
            status: 401,
            headers: ['Content-Type' => 'application/json'],
            body: Utils::streamFor(json_encode([
                'error' => 'Unauthorized',
                'message' => $message,
            ], JSON_THROW_ON_ERROR))
        );
    }
}
