<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action;

use Pradzikowski\Framework\Exception\HttpException;
use Pradzikowski\Framework\Exception\InvalidCredentialsException;
use Pradzikowski\Framework\Exception\UnauthorizedException;
use Pradzikowski\Framework\Http\RequestFactory;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class ActionRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private $action,
        private \Psr\Log\LoggerInterface $logger
    ) {
    }

    /**
     * Handle HTTP request by executing action and managing exceptions
     *
     * @param ServerRequestInterface $request HTTP request to process
     * @return ResponseInterface JSON response with action result or error
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actionRequest = new RequestFactory()->create($request);
        try {
            $responseModel = ($this->action)($actionRequest);
            $body = Utils::streamFor(json_encode($responseModel, JSON_THROW_ON_ERROR));
            return new PsrResponse(
                headers: ['Content-Type' => 'application/json'],
                body: $body
            );
        } catch (InvalidCredentialsException|UnauthorizedException $e) {
            $body = Utils::streamFor(json_encode(['error' => $e->getMessage()]));
            return new PsrResponse(
                status: 401,
                headers: ['Content-Type' => 'application/json'],
                body: $body
            );
        } catch (HttpException $e) {
            $body = Utils::streamFor(json_encode(['error' => $e->getMessage()]));
            return new PsrResponse(
                status: $e->getStatusCode(),
                headers: ['Content-Type' => 'application/json'],
                body: $body
            );
        } catch (Throwable $e) {
            $this->logger->error('Unhandled exception in ActionRequestHandler', [
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'class' => __CLASS__
            ]);

            $body = Utils::streamFor(json_encode(['error' => 'Internal server error']));
            return new PsrResponse(
                status: 500,
                headers: ['Content-Type' => 'application/json'],
                body: $body
            );
        }
    }
}
