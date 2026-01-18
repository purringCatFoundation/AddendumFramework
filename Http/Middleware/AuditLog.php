<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Middleware;

use Pradzikowski\Game\Enum\AuditEvent;
use Pradzikowski\Game\Enum\ResourceType;
use Pradzikowski\Framework\Http\MiddlewareOptions;
use Pradzikowski\Framework\Service\AuditLogger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Audit Log middleware
 *
 * Logs security-relevant actions using AuditLogger service.
 * Configured via #[Middleware(AuditLog::class, options: [...])]
 *
 * Options:
 * - event: AuditEvent enum (required)
 * - resourceType: ResourceType enum (optional)
 * - resourceParam: Route parameter name for resource UUID (optional)
 * - logFailures: Whether to log failures (default: false)
 *
 * Example:
 * #[Middleware(AuditLog::class, options: [
 *     'event' => AuditEvent::CHARACTER_DELETED,
 *     'resourceType' => ResourceType::CHARACTER,
 *     'resourceParam' => 'uuid',
 *     'logFailures' => true
 * ])]
 */
class AuditLog implements MiddlewareInterface
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly MiddlewareOptions $options
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var AuditEvent|null $event */
        $event = $this->options->get('event');
        /** @var ResourceType|null $resourceType */
        $resourceType = $this->options->get('resourceType');
        $resourceParam = $this->options->get('resourceParam');
        $logFailures = $this->options->get('logFailures');

        if (!$event) {
            // No event specified, skip logging
            return $handler->handle($request);
        }

        try {
            $response = $handler->handle($request);

            // Log success
            $this->logEvent($request, $event, $resourceType, $resourceParam, true);

            return $response;
        } catch (\Throwable $e) {
            // Log failure if configured
            if ($logFailures) {
                $this->logEvent($request, $event, $resourceType, $resourceParam, false, $e);
            }

            // Re-throw exception
            throw $e;
        }
    }

    /**
     * Log the event
     */
    private function logEvent(
        ServerRequestInterface $request,
        AuditEvent $event,
        ?ResourceType $resourceType,
        ?string $resourceParam,
        bool $success,
        ?\Throwable $exception = null
    ): void {
        $userUuid = $request->getAttribute('user_uuid');
        $resourceUuid = null;

        // Get resource UUID from route params if specified
        if ($resourceParam) {
            $routeParams = $request->getAttribute('route_params', []);
            $resourceUuid = $routeParams[$resourceParam] ?? null;
        }

        // Build metadata
        $metadata = [
            'method' => $request->getMethod(),
            'uri' => (string)$request->getUri(),
        ];

        // Add exception info if present
        if ($exception) {
            $metadata['error'] = [
                'message' => $exception->getMessage(),
                'class' => get_class($exception)
            ];
        }

        // Get IP and User Agent
        $serverParams = $request->getServerParams();
        $ipAddress = $this->getIpAddress($request);
        $userAgent = $serverParams['HTTP_USER_AGENT'] ?? null;

        // Log to audit logger
        $this->auditLogger->logSecurityEvent(
            event: $success ? $event : AuditEvent::tryFrom($event->failed()) ?? $event,
            userUuid: $userUuid,
            resourceType: $resourceType,
            resourceUuid: $resourceUuid,
            metadata: $metadata,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            success: $success
        );
    }

    /**
     * Get client IP address
     */
    private function getIpAddress(ServerRequestInterface $request): ?string
    {
        $serverParams = $request->getServerParams();

        // Check for forwarded IP (if behind proxy)
        if (isset($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        if (isset($serverParams['HTTP_X_REAL_IP'])) {
            return $serverParams['HTTP_X_REAL_IP'];
        }

        return $serverParams['REMOTE_ADDR'] ?? null;
    }
}
