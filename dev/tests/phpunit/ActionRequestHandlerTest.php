<?php

declare(strict_types=1);

namespace CitiesRpg\Tests;

use PCF\Addendum\Action\ActionRequestHandler;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Http\RequestFactory;
use PCF\Addendum\Exception\InvalidCredentialsException;
use PCF\Addendum\Exception\UnauthorizedException;
use GuzzleHttp\Psr7\ServerRequest;
use InvalidArgumentException;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ActionRequestHandlerTest extends TestCase
{
    public function testHandleInvokesActionAndReturnsResponse(): void
    {
        $expectedResponse = ['message' => 'Hello John'];
        $action = function (): array {
            return ['message' => 'Hello John'];
        };

        $logger = $this->createMock(LoggerInterface::class);
        $handler = new ActionRequestHandler($action, $logger);
        $serverRequest = new ServerRequest('GET', '/hello/John');
        
        $response = $handler->handle($serverRequest);
        
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(200, $response->getStatusCode());
        
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame($expectedResponse, $body);
    }

    public function testHandleWithInvalidCredentialsException(): void
    {
        $action = function (): never {
            throw new InvalidCredentialsException('Invalid credentials');
        };

        $logger = $this->createMock(LoggerInterface::class);
        $handler = new ActionRequestHandler($action, $logger);
        $serverRequest = new ServerRequest('GET', '/test');
        
        $response = $handler->handle($serverRequest);
        
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Invalid credentials', $body['error']);
    }

    public function testHandleWithUnauthorizedException(): void
    {
        $action = function (): never {
            throw new UnauthorizedException('Unauthorized access');
        };

        $logger = $this->createMock(LoggerInterface::class);
        $handler = new ActionRequestHandler($action, $logger);
        $serverRequest = new ServerRequest('GET', '/test');
        
        $response = $handler->handle($serverRequest);
        
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Unauthorized access', $body['error']);
    }

    public function testHandleWithGenericException(): void
    {
        $action = function (): never {
            throw new RuntimeException('Something went wrong');
        };

        $logger = $this->createMock(LoggerInterface::class);
        $handler = new ActionRequestHandler($action, $logger);
        $serverRequest = new ServerRequest('GET', '/test');
        
        $response = $handler->handle($serverRequest);
        
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Internal server error', $body['error']);
    }

    public function testHandleWithInvalidCredentialsFromDataProvider(): void
    {
        $exception = new InvalidCredentialsException('Bad creds');
        $expectedStatus = 401;
        
        $action = function () use ($exception): never {
            throw $exception;
        };

        $logger = $this->createMock(LoggerInterface::class);
        $handler = new ActionRequestHandler($action, $logger);
        $serverRequest = new ServerRequest('GET', '/test');
        
        $response = $handler->handle($serverRequest);
        
        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame($exception->getMessage(), $body['error']);
    }

    public function testHandleWithUnauthorizedFromDataProvider(): void
    {
        $exception = new UnauthorizedException('No access');
        $expectedStatus = 401;
        
        $action = function () use ($exception): never {
            throw $exception;
        };

        $logger = $this->createMock(LoggerInterface::class);
        $handler = new ActionRequestHandler($action, $logger);
        $serverRequest = new ServerRequest('GET', '/test');
        
        $response = $handler->handle($serverRequest);
        
        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame($exception->getMessage(), $body['error']);
    }

    public function testHandleWithRuntimeExceptionFromDataProvider(): void
    {
        $exception = new RuntimeException('Runtime error');
        $expectedStatus = 500;
        
        $action = function () use ($exception): never {
            throw $exception;
        };

        $logger = $this->createMock(LoggerInterface::class);
        $handler = new ActionRequestHandler($action, $logger);
        $serverRequest = new ServerRequest('GET', '/test');
        
        $response = $handler->handle($serverRequest);
        
        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Internal server error', $body['error']);
    }

    public function testHandleWithLogicExceptionFromDataProvider(): void
    {
        $exception = new \LogicException('Logic error');
        $expectedStatus = 500;
        
        $action = function () use ($exception): never {
            throw $exception;
        };

        $logger = $this->createMock(LoggerInterface::class);
        $handler = new ActionRequestHandler($action, $logger);
        $serverRequest = new ServerRequest('GET', '/test');
        
        $response = $handler->handle($serverRequest);
        
        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Internal server error', $body['error']);
    }

    public function testHandleWithInvalidArgumentExceptionFromDataProvider(): void
    {
        $exception = new InvalidArgumentException('Invalid arg');
        $expectedStatus = 500;
        
        $action = function () use ($exception): never {
            throw $exception;
        };

        $logger = $this->createMock(LoggerInterface::class);
        $handler = new ActionRequestHandler($action, $logger);
        $serverRequest = new ServerRequest('GET', '/test');
        
        $response = $handler->handle($serverRequest);
        
        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Internal server error', $body['error']);
    }

    public function testActionReceivesCorrectRequest(): void
    {
        $capturedRequest = null;
        $action = function (Request $request) use (&$capturedRequest): array {
            $capturedRequest = $request;
            return ['status' => 'ok'];
        };

        $logger = $this->createMock(LoggerInterface::class);
        $handler = new ActionRequestHandler($action, $logger);
        $serverRequest = new ServerRequest('GET', '/test');
        
        $handler->handle($serverRequest);
        
        $this->assertInstanceOf(Request::class, $capturedRequest);
    }
}