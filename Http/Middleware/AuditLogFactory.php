<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Middleware;

use Pradzikowski\Framework\Http\Middleware\AuditLog;
use Pradzikowski\Framework\Http\Middleware\MiddlewareFactoryInterface;
use Pradzikowski\Framework\Database\DbConnectionFactory;
use Pradzikowski\Framework\Http\MiddlewareOptions;
use Pradzikowski\Framework\Service\AuditLoggerFactory;

class AuditLogFactory implements MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): AuditLog
    {
        $pdo = new DbConnectionFactory()->create();
        $auditLogger = new AuditLoggerFactory($pdo)->create();

        return new AuditLog($auditLogger, $options);
    }
}
