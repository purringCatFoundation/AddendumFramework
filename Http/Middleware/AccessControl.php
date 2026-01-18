<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Middleware;

use Pradzikowski\Framework\Attribute\AccessControl as AccessControlAttribute;
use \Pradzikowski\Framework\Guardian\AccessControlGuardianInterface;
use Pradzikowski\Framework\Auth\Session;
use Pradzikowski\Framework\Exception\AuthorizationError;
use Pradzikowski\Framework\Exception\PermissionDenied;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;

/**
 * AccessControl Middleware using PHP 8.5 guardian pattern
 *
 * This middleware processes #[AccessControl] attributes on action classes
 * and executes the specified guardians to validate access.
 *
 * Guardians can be:
 * 1. Classes implementing AccessControlGuardianInterface
 * 2. Callables with signature: fn(ServerRequestInterface, Session): bool
 *
 * The middleware:
 * - Extracts Session from request attributes
 * - Reads AccessControl attributes from action class
 * - Instantiates/calls guardians
 * - Handles PermissionDenied (403) and AuthorizationError (401) exceptions
 * - Returns appropriate error responses
 */
class AccessControl implements MiddlewareInterface
{
    public function __construct(
        private readonly ?ContainerInterface $container = null,
        private readonly ?string $actionClass = null
    ) {
    }

    /**
     * Process request with access control validation
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get action class from constructor (set by factory) or request attribute
        $actionClass = $this->actionClass ?? $request->getAttribute('action_class');

        if (!$actionClass) {
            // No action class set, continue without access control
            return $handler->handle($request);
        }

        // Get access control attributes
        $accessControls = $this->getAccessControlAttributes($actionClass);

        if (empty($accessControls)) {
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
            foreach ($accessControls as $accessControl) {
                $this->executeGuardian($accessControl, $request, $session);
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
     * Extract AccessControl attributes from action class
     *
     * @return AccessControlAttribute[]
     */
    private function getAccessControlAttributes(string $actionClass): array
    {
        if (!class_exists($actionClass)) {
            return [];
        }

        $reflection = new ReflectionClass($actionClass);
        $attributes = $reflection->getAttributes(AccessControlAttribute::class);

        return array_map(
            fn($attr) => $attr->newInstance(),
            $attributes
        );
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
        AccessControlAttribute $accessControl,
        ServerRequestInterface $request,
        Session $session
    ): void {
        $guardian = $accessControl->getGuardian();

        if ($accessControl->isClass()) {
            // Guardian is a class - instantiate and call authorize()
            $guardianInstance = $this->instantiateGuardian($guardian);
            $guardianInstance->authorize($request, $session);
        } elseif ($accessControl->isCallable()) {
            // Guardian is a callable - invoke directly
            $result = $guardian($request, $session);

            // If callable returns false, deny access
            if ($result === false) {
                throw new PermissionDenied('Access denied by guardian');
            }

            // If callable returns true or throws exception, that's handled
        } else {
            throw new AuthorizationError('Invalid guardian type');
        }
    }

    /**
     * Instantiate a guardian class
     */
    private function instantiateGuardian(string $guardianClass): AccessControlGuardianInterface
    {
        // Try to get from container first (for dependency injection)
        if ($this->container && $this->container->has($guardianClass)) {
            $instance = $this->container->get($guardianClass);

            if (!$instance instanceof AccessControlGuardianInterface) {
                throw new \RuntimeException(
                    "Guardian '{$guardianClass}' from container does not implement AccessControlGuardianInterface"
                );
            }

            return $instance;
        }

        // Fallback: instantiate directly
        $reflection = new \ReflectionClass($guardianClass);

        // Check if constructor has no required parameters
        $constructor = $reflection->getConstructor();

        if ($constructor && $constructor->getNumberOfRequiredParameters() > 0) {
            throw new \RuntimeException(
                "Cannot instantiate guardian '{$guardianClass}': constructor requires parameters. " .
                "Use a factory or configure dependency injection."
            );
        }

        return new $guardianClass();
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
