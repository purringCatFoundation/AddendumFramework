<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action\User;

use Pradzikowski\Framework\Action\ActionInterface;
use Pradzikowski\Framework\Attribute\Middleware;
use Pradzikowski\Framework\Attribute\Route;
use Pradzikowski\Framework\Attribute\ValidateRequest;
use Pradzikowski\Framework\Auth\AuthService;
use Pradzikowski\Framework\Auth\TokenType;
use Pradzikowski\Framework\Http\Request;
use Pradzikowski\Framework\Response\User\RefreshResponse;
use Pradzikowski\Game\Validation\JwtToken;

/**
 * Example request:
 * POST /sessions/refresh
 * Authorization: Bearer <refresh_token>
 *
 * Example response:
 * {
 *   "access_token": "..."
 * }
 */
#[Route(path: '/sessions/refresh', method: 'GET', requirements: ['id' => '[A-Za-z0-9-]+'])]
#[ValidateRequest('jwt_token', new JwtToken(TokenType::USER_REFRESH), ValidateRequest::SOURCE_HEADER)]
class GetRefreshSessionAction implements ActionInterface
{
    public function __construct(private readonly AuthService $service)
    {
    }

    /**
     * Refresh access token using validated refresh token
     *
     * @param Request $request HTTP request with validated refresh token
     * @return RefreshResponse Response containing new access token
     */
    public function __invoke(Request $request): RefreshResponse
    {
        $token = $request->get('jwt_token');
        $fingerprint = $request->getHeaderLine('X-Request-Fingerprint');

        $tokens = $this->service->refresh($token, $fingerprint);
        return new RefreshResponse($tokens['access_token'], $tokens['refresh_token']);
    }
}
