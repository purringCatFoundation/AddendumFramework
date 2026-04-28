<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\User;

use PCF\Addendum\Action\ActionInterface;
use PCF\Addendum\Attribute\RateLimit;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Attribute\ValidateRequest;
use PCF\Addendum\Auth\AuthService;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Response\User\LoginResponse;
use PCF\Addendum\Validation\Rules\Email;
use PCF\Addendum\Validation\Rules\Required;

/**
 * Login action - creates a new session
 *
 * Example request:
 * POST /v1/sessions
 * {
 *   "email": "john@example.com",
 *   "password": "secret"
 * }
 *
 * Example response:
 * {
 *   "access_token": "...",
 *   "refresh_token": "..."
 * }
 */
#[Route(path: '/v1/sessions', method: 'POST')]
#[RateLimit(maxAttempts: 5, windowSeconds: 300, scope: RateLimit::SCOPE_ACCOUNT)]
#[ValidateRequest('email', new Required(), new Email())]
#[ValidateRequest('password', new Required())]
class PostSessionAction implements ActionInterface
{
    public function __construct(private readonly AuthService $service)
    {
    }

    public function __invoke(Request $request): LoginResponse
    {
        $data = $request->json();
        $fingerprint = $request->getHeaderLine('X-Request-Fingerprint');
        $tokens = $this->service->login($data['email'] ?? '', $data['password'] ?? '', $fingerprint);

        return new LoginResponse($tokens->accessToken, $tokens->refreshToken);
    }
}
