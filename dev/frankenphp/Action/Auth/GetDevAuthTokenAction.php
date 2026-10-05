<?php
declare(strict_types=1);

namespace PCF\Addendum\Dev\Action\Auth;

use JsonSerializable;
use PCF\Addendum\Action\ActionInterface;
use PCF\Addendum\Attribute\Middleware;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Auth\Session;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Http\Request;

#[Route(path: '/dev/auth/token', method: 'GET')]
#[Middleware(Auth::class)]
final class GetDevAuthTokenAction implements ActionInterface
{
    public function __invoke(Request $request): JsonSerializable
    {
        $tokenPayload = $request->get('token_payload');
        $session = $request->get('session');
        $fingerprint = $request->getHeaderLine('X-Request-Fingerprint');

        return new DevAuthTokenResponse(
            tokenPayload: $tokenPayload instanceof TokenPayload ? $tokenPayload : null,
            session: $session instanceof Session ? $session : null,
            request: [
                'method' => $request->getMethod(),
                'target' => $request->getRequestTarget(),
                'fingerprint' => $fingerprint,
                'fingerprintHash' => $fingerprint !== '' ? sha1($fingerprint) : null,
                'signatureTimestamp' => $request->getHeaderLine('X-Request-Timestamp'),
                'signatureNonce' => $request->getHeaderLine('X-Request-Nonce'),
            ],
            attributes: [
                'userUuid' => $request->get('user_uuid'),
                'jti' => $request->get('jti'),
                'sessionId' => $request->get('session_id'),
                'fingerprintHash' => $request->get('fingerprint_hash'),
                'tokenIssuedAt' => $request->get('token_issued_at'),
                'tokenExpiresAt' => $request->get('token_expires_at'),
                'tokenType' => $request->get('token_type'),
            ]
        );
    }
}

final class GetDevAuthTokenActionFactory
{
    public function create(): GetDevAuthTokenAction
    {
        return new GetDevAuthTokenAction();
    }
}

final readonly class DevAuthTokenResponse implements JsonSerializable
{
    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private ?TokenPayload $tokenPayload,
        private ?Session $session,
        private array $request,
        private array $attributes
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'authorized' => true,
            'token' => $this->tokenPayload?->jsonSerialize(),
            'session' => $this->session?->toArray(),
            'request' => $this->request,
            'attributes' => $this->attributes,
        ];
    }
}
