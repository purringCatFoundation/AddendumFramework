<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\User;

use PCF\Addendum\Action\ActionInterface;
use PCF\Addendum\Attribute\Middleware;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Exception\HttpException;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Response\NoContentResponse;

/**
 * Delete current user's account
 *
 * Example request:
 * DELETE /v1/users/me
 * Authorization: Bearer <access_token>
 *
 * Example response: 204 No Content
 */
#[Route(path: '/v1/users/me', method: 'DELETE')]
#[Middleware(Auth::class)]
class DeleteUserAction implements ActionInterface
{
    public function __invoke(Request $request): NoContentResponse
    {
        $userUuid = $request->get('user_uuid');

        if (!$userUuid) {
            throw HttpException::unauthorized('User not authenticated');
        }

        // TODO: Implement user deletion logic via AuthRepository
        // This should:
        // 1. Revoke all user's tokens
        // 2. Delete user account
        // $this->authRepository->deleteUser($userUuid);

        return new NoContentResponse();
    }
}
