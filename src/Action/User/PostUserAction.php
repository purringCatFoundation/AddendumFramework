<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\User;

use PCF\Addendum\Action\ActionInterface;
use PCF\Addendum\Attribute\RateLimit;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Attribute\ValidateRequest;
use PCF\Addendum\Auth\AuthService;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Response\User\RegisterResponse;
use PCF\Addendum\Validation\Rules\Email;
use PCF\Addendum\Validation\Rules\MaxLength;
use PCF\Addendum\Validation\PasswordStrength;
use PCF\Addendum\Validation\Rules\Required;

/**
 * Register a new user
 *
 * Example request:
 * POST /v1/users
 * {
 *   "email": "john@example.com",
 *   "password": "StrongP@ssw0rd123"
 * }
 *
 * Example response (201 Created):
 * {
 *   "uuid": "11111111-1111-1111-1111-111111111111",
 *   "email": "john@example.com"
 * }
 */
#[Route(path: '/v1/users', method: 'POST')]
#[RateLimit(maxAttempts: 5, windowSeconds: 300, scope: RateLimit::SCOPE_ACCOUNT)]
#[ValidateRequest('email', new Required(), new Email(), new MaxLength(255))]
#[ValidateRequest('password', new Required(), new PasswordStrength())]
class PostUserAction implements ActionInterface
{
    public function __construct(private AuthService $service)
    {
    }

    public function __invoke(Request $request): RegisterResponse
    {
        $data = $request->json();
        $user = $this->service->register($data['email'] ?? '', $data['password'] ?? '');

        return new RegisterResponse($user['uuid'], $user['email']);
    }
}
