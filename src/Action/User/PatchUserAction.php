<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\User;

use PCF\Addendum\Action\ActionInterface;
use PCF\Addendum\Attribute\Middleware;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Attribute\ValidateRequest;
use PCF\Addendum\Exception\HttpException;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Response\User\ProfileResponse;
use PCF\Addendum\Validation\Rules\Email;
use PCF\Addendum\Validation\Rules\MaxLength;

/**
 * Update current user's profile
 *
 * Example request:
 * PATCH /v1/users/me
 * Authorization: Bearer <access_token>
 * {
 *   "email": "newemail@example.com"
 * }
 *
 * Example response:
 * {
 *   "uuid": "11111111-1111-1111-1111-111111111111"
 * }
 */
#[Route(path: '/v1/users/me', method: 'PATCH')]
#[Middleware(Auth::class)]
#[ValidateRequest('email', new Email(), new MaxLength(255))]
class PatchUserAction implements ActionInterface
{
    public function __invoke(Request $request): ProfileResponse
    {
        $userUuid = $request->get('user_uuid');

        if (!$userUuid) {
            throw HttpException::unauthorized('User not authenticated');
        }

        // TODO: Implement user update logic via AuthRepository
        // $data = $request->json();
        // $this->authRepository->updateUser($userUuid, $data);

        return new ProfileResponse($userUuid);
    }
}
