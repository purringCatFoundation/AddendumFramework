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
use Pradzikowski\Framework\Response\User\LoginResponse;
use Pradzikowski\Game\Validation\Email;
use Pradzikowski\Game\Validation\Required;

/**
 * Example request:
 * POST /sessions
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
#[Route(path: '/sessions', method: 'POST')]
#[RateLimit(maxAttempts: 5, windowSeconds: 300, scope: RateLimit::SCOPE_ACCOUNT)]  // Override: stricter for auth
#[ValidateRequest('email', new Required(), new Email())]
#[ValidateRequest('password', new Required())]
#[Middleware(AuditLog::class, options: ['event' => AuditEvent::AUTH_LOGIN, 'logFailures' => true])]
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
        return new LoginResponse($tokens['access_token'], $tokens['refresh_token']);
    }
}
