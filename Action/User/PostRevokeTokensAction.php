<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action\User;

use Pradzikowski\Framework\Action\ActionInterface;
use Pradzikowski\Framework\Attribute\Middleware;
use Pradzikowski\Framework\Attribute\Route;
use Pradzikowski\Framework\Attribute\ValidateRequest;
use Pradzikowski\Framework\Auth\TokenValidationRepository;
use Pradzikowski\Framework\Http\Request;
use Pradzikowski\Framework\Http\Middleware\Auth;
use Pradzikowski\Framework\Response\GenericResponse;
use Pradzikowski\Game\Validation\Required;
use InvalidArgumentException;

/**
 * Administrative endpoint to revoke tokens
 *
 * Example request (revoke specific user):
 * POST /admin/revoke-tokens
 * Authorization: Bearer <admin_access_token>
 * {
 *   "user_uuid": "11111111-1111-1111-1111-111111111111",
 *   "reason": "security_incident"
 * }
 *
 * Example request (revoke all tokens globally):
 * POST /admin/revoke-tokens
 * Authorization: Bearer <admin_access_token>
 * {
 *   "global": true,
 *   "reason": "system_maintenance"
 * }
 *
 * Example response:
 * {
 *   "success": true,
 *   "message": "Tokens revoked successfully"
 * }
 */
#[Route(path: '/admin/revoke-tokens', method: 'POST')]
#[Middleware(Auth::class)]
#[ValidateRequest('reason', new Required())]
class PostRevokeTokensAction implements ActionInterface
{
    public function __construct(
        private readonly TokenValidationRepository $tokenValidationRepository
    ) {
    }

    /**
     * Revoke tokens globally or for specific user
     *
     * @param Request $request HTTP request with revocation parameters
     * @return GenericResponse Success response with revocation details
     * @throws InvalidArgumentException When neither user_uuid nor global flag specified
     */
    public function __invoke(Request $request): GenericResponse
    {
        $data = $request->json();
        $adminUuid = $request->get('user_uuid');
        $reason = $data['reason'];

        if (isset($data['global']) && $data['global'] === true) {
            $this->tokenValidationRepository->revokeAllTokens($reason, $adminUuid);
            $message = 'All tokens revoked globally';
        } elseif (isset($data['user_uuid'])) {
            $targetUserUuid = $data['user_uuid'];
            $this->tokenValidationRepository->revokeUserTokens($targetUserUuid, $reason, $adminUuid);
            $message = "Tokens revoked for user: {$targetUserUuid}";
        } else {
            throw new InvalidArgumentException('Either user_uuid or global=true must be specified');
        }

        return new GenericResponse(true, $message);
    }
}
