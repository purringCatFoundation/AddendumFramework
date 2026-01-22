<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\User;

use PCF\Addendum\Action\ActionInterface;
use PCF\Addendum\Attribute\Middleware;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Response\User\ProfileResponse;

/**
 * Get user profile
 *
 * Example request:
 * GET /v1/users/me
 * Authorization: Bearer <access_token>
 *
 * Example response:
 * {
 *   "uuid": "11111111-1111-1111-1111-111111111111"
 * }
 */
#[Route(path: '/v1/users/me', method: 'GET')]
#[Route(path: '/v1/users/:userUuid', method: 'GET')]
#[Middleware(Auth::class)]
class GetUserAction implements ActionInterface
{
    public function __invoke(Request $request): ProfileResponse
    {
        $uuid = $request->get('user_uuid');

        return new ProfileResponse($uuid);
    }
}
