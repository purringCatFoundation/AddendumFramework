<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\User;

use PCF\Addendum\Action\ActionInterface;
use PCF\Addendum\Attribute\Middleware;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Auth\AuthService;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Response\NoContentResponse;

/**
 * Logout action - revokes current session
 *
 * Example request:
 * DELETE /v1/sessions/current
 * Authorization: Bearer <access_token>
 *
 * Example response: 204 No Content
 */
#[Route(path: '/v1/sessions/current', method: 'DELETE')]
#[Middleware(Auth::class)]
class DeleteSessionAction implements ActionInterface
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function __invoke(Request $request): NoContentResponse
    {
        $userUuid = $request->get('user_uuid');

        $this->authService->logout($userUuid, 'user_logout');

        return new NoContentResponse();
    }
}
