<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action\User;

use Pradzikowski\Framework\Action\ActionInterface;
use Pradzikowski\Game\Attribute\RateLimit;
use Pradzikowski\Framework\Attribute\Middleware;
use Pradzikowski\Framework\Attribute\Route;
use Pradzikowski\Framework\Attribute\ValidateRequest;
use Pradzikowski\Framework\Auth\AuthService;
use Pradzikowski\Game\Enum\AuditEvent;
use Pradzikowski\Framework\Http\Request;
use Pradzikowski\Framework\Http\Middleware\AuditLog;
use Pradzikowski\Framework\Response\User\RegisterResponse;
use Pradzikowski\Game\Validation\Email;
use Pradzikowski\Game\Validation\MaxLength;
use Pradzikowski\Framework\Validation\PasswordStrength;
use Pradzikowski\Game\Validation\Required;

/**
 * Example request:
 * POST /users
 * {
 *   "email": "john@example.com",
 *   "password": "StrongP@ssw0rd123"
 * }
 *
 * Example response:
 * {
 *   "uuid": "11111111-1111-1111-1111-111111111111",
 *   "email": "john@example.com"
 * }
 */
#[Route(path: '/users/register', method: 'POST')]
#[RateLimit(maxAttempts: 5, windowSeconds: 300, scope: RateLimit::SCOPE_ACCOUNT)]  // Override: stricter than default
#[ValidateRequest('email', new Required(), new Email(), new MaxLength(255))]
#[ValidateRequest('password', new Required(), new PasswordStrength())]
#[Middleware(AuditLog::class, options: ['event' => AuditEvent::USER_REGISTERED, 'logFailures' => true])]
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
