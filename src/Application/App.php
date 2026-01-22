<?php
declare(strict_types=1);

namespace PCF\Addendum\Application;

use PCF\Addendum\Http\Router;
use PCF\Addendum\Action\ActionRequestHandlerFactory;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class App implements RequestHandlerInterface
{
    public function __construct(
        private Router $router,
        private LoggerInterface $logger
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $match = $this->router->match($request);
            if ($match === null) {
                $body = Utils::streamFor(json_encode(['error' => 'Not found']));
                return new PsrResponse(
                    status: 404,
                    headers: ['Content-Type' => 'application/json'],
                    body: $body
                );
            }
            $handler = new ActionRequestHandlerFactory($this->logger)->create($match);

            return $handler->handle($match->request);
        } catch (\Throwable $e) {
            $this->logger->error('Unhandled exception in application', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $body = Utils::streamFor(json_encode([
                'error' => 'Internal server error',
                'message' => ($_ENV['DEBUG'] ?? 'false') === 'true' ? $e->getMessage() : 'An unexpected error occurred'
            ]));

            return new PsrResponse(
                status: 500,
                headers: ['Content-Type' => 'application/json'],
                body: $body
            );
        }
    }
}
