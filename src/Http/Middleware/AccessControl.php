<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Auth\Session;
use PCF\Addendum\Exception\AuthorizationError;
use PCF\Addendum\Exception\PermissionDenied;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * AccessControl Middleware using PHP 8.5 guardian pattern
 *
 * This middleware executes compiled guardian definitions to validate access.
 *
 * The middleware:
 * - Extracts Session from request attributes
 * - Executes guardian definitions
 * - Handles PermissionDenied (403) and AuthorizationError (401) exceptions
 * - Returns appropriate error responses
 */
class AccessControl implements MiddlewareInterface
{
    public function __construct(
        private readonly AccessControlGuardianCollection $compiledGuardians = new AccessControlGuardianCollection()
    ) {
    }

    /**
     * Process request with access control validation
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get access control attributes
        $guardians = $this->getAccessControlGuardians();

        if ($guardians->isEmpty()) {
            // No access control requirements, allow access
            return $handler->handle($request);
        }

        // Extract session from request
        try {
            $session = $this->extractSession($request);
        } catch (AuthorizationError $e) {
            return $this->createErrorResponse($e);
        }

        // Execute all guardians (all must pass)
        try {
            foreach ($guardians as $guardian) {
                $this->executeGuardian($guardian, $request, $session);
            }
        } catch (PermissionDenied | AuthorizationError $e) {
            return $this->createErrorResponse($e);
        } catch (\Throwable $e) {
            // Unexpected error - treat as authorization error
            error_log("Access control error: " . $e->getMessage());
            return $this->createErrorResponse(
                new AuthorizationError('Authorization check failed: ' . $e->getMessage())
            );
        }

        return $handler->handle($request);
    }

    /**
     */
    private function getAccessControlGuardians(): AccessControlGuardianCollection
    {
        return $this->compiledGuardians;
    }

    /**
     * Extract session from request attributes
     *
     * @throws AuthorizationError If session cannot be extracted
     */
    private function extractSession(ServerRequestInterface $request): Session
    {
        // Try to get session from request attributes (set by Auth middleware)
        $session = $request->getAttribute('session');

        if ($session instanceof Session) {
            return $session;
        }

        // Try to construct from token_payload
        $tokenPayload = $request->getAttribute('token_payload');

        if ($tokenPayload) {
            return Session::fromTokenPayload($tokenPayload);
        }

        // Try to construct from individual attributes
        $userUuid = $request->getAttribute('user_uuid');

        if ($userUuid) {
            return Session::fromRequest($request);
        }

        throw AuthorizationError::missingSession();
    }

    /**
     * Execute a guardian
     *
     * @throws PermissionDenied When guardian denies access
     * @throws AuthorizationError When authorization check fails
     */
    private function executeGuardian(
        AccessControlGuardianDefinitionInterface $guardian,
        ServerRequestInterface $request,
        Session $session
    ): void {
        $guardian->authorize($request, $session);
    }

    /**
     * Create error response from exception
     */
    private function createErrorResponse(PermissionDenied|AuthorizationError $exception): ResponseInterface
    {
        $statusCode = $exception->getHttpStatusCode();

        $body = Utils::streamFor(json_encode($exception->toArray()));

        return new PsrResponse(
            $statusCode,
            ['Content-Type' => 'application/json'],
            $body
        );
    }
}
