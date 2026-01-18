<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action\User;

use Pradzikowski\Framework\Action\ActionInterface;
use Pradzikowski\Framework\Attribute\Middleware;
use Pradzikowski\Framework\Attribute\Route;
use Pradzikowski\Framework\Http\Request;
use Pradzikowski\Framework\Http\Middleware\Auth;
use Pradzikowski\Framework\Response\User\ProfileResponse;

/**
 * Example request:
 * GET /users/me
 * Authorization: Bearer <access_token>
 *
 * Example response:
 * {
 *   "uuid": "11111111-1111-1111-1111-111111111111"
 * }
 */
#[Route(path: '/users/me', method: 'GET')]
#[Route(path: '/users/:user_uuid', method: 'GET')]
#[Middleware(Auth::class)]
class GetUserAction implements ActionInterface
{
    public function __invoke(Request $request): ProfileResponse
    {
        $uuid = $request->get('user_uuid');
        return new ProfileResponse($uuid);
    }
}
