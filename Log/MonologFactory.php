<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Log;

use Pradzikowski\Framework\Config\SystemEnvironmentProvider;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerInterface;

class MonologFactory
{
    public function __construct(
        private SystemEnvironmentProvider $environmentProvider
    ) {
    }

    public function create(): LoggerInterface
    {
        $logger = new Logger('api-backend');

        $logFile = $this->environmentProvider->get('LOG_FILE', 'var/log/app.log');
        $handler = new StreamHandler($logFile, Logger::DEBUG);
        $formatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context%\n", 'Y-m-d H:i:s');
        $handler->setFormatter($formatter);

        $logger->pushHandler($handler);

        return $logger;
    }
}