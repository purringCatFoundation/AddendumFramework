<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\Admin;

use PCF\Addendum\Action\ActionInterface;
use PCF\Addendum\Attribute\Middleware;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Attribute\ValidateRequest;
use PCF\Addendum\Auth\TokenValidationRepository;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Response\NoContentResponse;
use PCF\Addendum\Validation\Rules\Required;
use InvalidArgumentException;

/**
 * Administrative endpoint to revoke tokens
 *
 * Example request (revoke specific user):
 * DELETE /v1/admin/tokens
 * Authorization: Bearer <admin_access_token>
 * {
 *   "userUuid": "11111111-1111-1111-1111-111111111111",
 *   "reason": "security_incident"
 * }
 *
 * Example request (revoke all tokens globally):
 * DELETE /v1/admin/tokens
 * Authorization: Bearer <admin_access_token>
 * {
 *   "global": true,
 *   "reason": "system_maintenance"
 * }
 *
 * Example response: 204 No Content
 */
#[Route(path: '/v1/admin/tokens', method: 'DELETE')]
#[Middleware(Auth::class)]
#[ValidateRequest('reason', new Required())]
class DeleteAdminTokensAction implements ActionInterface
{
    public function __construct(
        private readonly TokenValidationRepository $tokenValidationRepository
    ) {
    }

    public function __invoke(Request $request): NoContentResponse
    {
        $data = $request->json();
        $adminUuid = $request->get('user_uuid');
        $reason = $data['reason'];

        if (isset($data['global']) && $data['global'] === true) {
            $this->tokenValidationRepository->revokeAllTokens($reason, $adminUuid);
        } elseif (isset($data['userUuid'])) {
            $targetUserUuid = $data['userUuid'];
            $this->tokenValidationRepository->revokeUserTokens($targetUserUuid, $reason, $adminUuid);
        } else {
            throw new InvalidArgumentException('Either userUuid or global=true must be specified');
        }

        return new NoContentResponse();
    }
}
