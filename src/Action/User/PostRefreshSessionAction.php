<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\User;

use PCF\Addendum\Action\ActionInterface;
use PCF\Addendum\Attribute\Middleware;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Attribute\ValidateRequest;
use PCF\Addendum\Auth\AuthService;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Response\User\RefreshResponse;
use PCF\Addendum\Validation\Rules\JwtToken;

/**
 * Refresh access token using a valid refresh token
 *
 * Example request:
 * POST /v1/sessions/refresh
 * Authorization: Bearer <refresh_token>
 *
 * Example response:
 * {
 *   "access_token": "...",
 *   "refresh_token": "..."
 * }
 */
#[Route(path: '/v1/sessions/refresh', method: 'POST')]
#[ValidateRequest('jwt_token', new JwtToken(TokenType::USER_REFRESH), ValidateRequest::SOURCE_HEADER)]
#[Middleware(Auth::class)]
class PostRefreshSessionAction implements ActionInterface
{
    public function __construct(private readonly AuthService $service)
    {
    }

    public function __invoke(Request $request): RefreshResponse
    {
        $token = $request->get('jwt_token');
        $fingerprint = $request->getHeaderLine('X-Request-Fingerprint');

        $tokens = $this->service->refresh($token, $fingerprint);

        return new RefreshResponse($tokens['access_token'], $tokens['refresh_token']);
    }
}
