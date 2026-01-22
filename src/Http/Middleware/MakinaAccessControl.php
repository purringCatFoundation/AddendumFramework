<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Auth\SessionSubjectLocator;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use MakinaCorpus\AccessControl\Authorization;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * makinacorpus/access-control integration middleware
 *
 * Processes makinacorpus attributes (#[AccessRole], #[AccessPermission], etc.)
 * and enforces access control using the Authorization service.
 *
 * This middleware should run AFTER Auth middleware (which sets the session).
 */
class MakinaAccessControl implements MiddlewareInterface
{
    public function __construct(
        private readonly Authorization $authorization,
        private readonly ?LoggerInterface $logger = null
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $actionClass = $request->getAttribute('action_class');

        if (!$actionClass) {
            return $handler->handle($request);
        }

        // Get session from request (set by Auth middleware)
        $session = $request->getAttribute('session');

        // Set current session for SubjectLocator
        SessionSubjectLocator::setCurrentSession($session);

        try {
            // Check access using makinacorpus Authorization
            // This will process all #[AccessRole], #[AccessPermission], etc. attributes
            $granted = $this->authorization->isGranted($actionClass, $session);

            if (!$granted) {
                return $this->createForbiddenResponse('Access denied');
            }

            $response = $handler->handle($request);

            return $response;
        } catch (\Throwable $e) {
            // Log access control errors
            if ($this->logger) {
                $this->logger->warning('Access control check failed', [
                    'action' => $actionClass,
                    'user_uuid' => $session?->userUuid,
                    'error' => $e->getMessage()
                ]);
            }

            // Treat errors as access denied (fail secure)
            return $this->createForbiddenResponse('Access denied');
        } finally {
            // Clear session from SubjectLocator
            SessionSubjectLocator::setCurrentSession(null);
        }
    }

    /**
     * Create forbidden response
     */
    private function createForbiddenResponse(string $message): ResponseInterface
    {
        return new Response(
            403,
            ['Content-Type' => 'application/json'],
            Utils::streamFor(json_encode([
                'error' => 'Forbidden',
                'message' => $message
            ]))
        );
    }
}
