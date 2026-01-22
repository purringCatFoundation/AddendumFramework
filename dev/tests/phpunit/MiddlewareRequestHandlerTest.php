<?php

declare(strict_types=1);

namespace CitiesRpg\Tests;

use PCF\Addendum\Http\MiddlewareRequestHandler;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class MiddlewareRequestHandlerTest extends TestCase
{
    public function testHandleProcessesMiddlewareAndDelegates(): void
    {
        $expectedResponse = new PsrResponse(200, ['X-Test' => 'success']);
        
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($request, $handler) {
                return $handler->handle($request);
            });

        $handler = new MiddlewareRequestHandler($middleware, $nextHandler);
        $request = new ServerRequest('GET', '/');
        
        $response = $handler->handle($request);
        
        $this->assertSame($expectedResponse, $response);
    }

    public function testMiddlewareCanModifyRequest(): void
    {
        $capturedRequest = null;
        
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($request) use (&$capturedRequest) {
                $capturedRequest = $request;
                return new PsrResponse();
            });

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($request, $handler) {
                $modifiedRequest = $request->withAttribute('middleware-added', 'value');
                return $handler->handle($modifiedRequest);
            });

        $handler = new MiddlewareRequestHandler($middleware, $nextHandler);
        $originalRequest = new ServerRequest('GET', '/');
        
        $handler->handle($originalRequest);
        
        $this->assertSame('value', $capturedRequest->getAttribute('middleware-added'));
    }

    public function testMiddlewareCanModifyResponse(): void
    {
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse(200));

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($request, $handler) {
                $response = $handler->handle($request);
                return $response->withHeader('X-Middleware', 'processed');
            });

        $handler = new MiddlewareRequestHandler($middleware, $nextHandler);
        $request = new ServerRequest('GET', '/');
        
        $response = $handler->handle($request);
        
        $this->assertSame('processed', $response->getHeaderLine('X-Middleware'));
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler->expects($this->never())
            ->method('handle');

        $shortCircuitResponse = new PsrResponse(403, [], 'Forbidden');
        
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturn($shortCircuitResponse);

        $handler = new MiddlewareRequestHandler($middleware, $nextHandler);
        $request = new ServerRequest('GET', '/');
        
        $response = $handler->handle($request);
        
        $this->assertSame($shortCircuitResponse, $response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Forbidden', (string) $response->getBody());
    }

    public function testHandleWorksWithGetMethod(): void
    {
        $method = 'GET';
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse(200));

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($request, $handler) {
                return $handler->handle($request);
            });

        $handler = new MiddlewareRequestHandler($middleware, $nextHandler);
        $request = new ServerRequest($method, '/test');
        
        $response = $handler->handle($request);
        
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHandleWorksWithPostMethod(): void
    {
        $method = 'POST';
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse(200));

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($request, $handler) {
                return $handler->handle($request);
            });

        $handler = new MiddlewareRequestHandler($middleware, $nextHandler);
        $request = new ServerRequest($method, '/test');
        
        $response = $handler->handle($request);
        
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHandleWorksWithPutMethod(): void
    {
        $method = 'PUT';
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse(200));

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($request, $handler) {
                return $handler->handle($request);
            });

        $handler = new MiddlewareRequestHandler($middleware, $nextHandler);
        $request = new ServerRequest($method, '/test');
        
        $response = $handler->handle($request);
        
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHandleWorksWithDeleteMethod(): void
    {
        $method = 'DELETE';
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse(200));

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($request, $handler) {
                return $handler->handle($request);
            });

        $handler = new MiddlewareRequestHandler($middleware, $nextHandler);
        $request = new ServerRequest($method, '/test');
        
        $response = $handler->handle($request);
        
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHandleWorksWithPatchMethod(): void
    {
        $method = 'PATCH';
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse(200));

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($request, $handler) {
                return $handler->handle($request);
            });

        $handler = new MiddlewareRequestHandler($middleware, $nextHandler);
        $request = new ServerRequest($method, '/test');
        
        $response = $handler->handle($request);
        
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHandleWorksWithOptionsMethod(): void
    {
        $method = 'OPTIONS';
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse(200));

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($request, $handler) {
                return $handler->handle($request);
            });

        $handler = new MiddlewareRequestHandler($middleware, $nextHandler);
        $request = new ServerRequest($method, '/test');
        
        $response = $handler->handle($request);
        
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHandleWorksWithHeadMethod(): void
    {
        $method = 'HEAD';
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse(200));

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($request, $handler) {
                return $handler->handle($request);
            });

        $handler = new MiddlewareRequestHandler($middleware, $nextHandler);
        $request = new ServerRequest($method, '/test');
        
        $response = $handler->handle($request);
        
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMiddlewareReceivesCorrectParameters(): void
    {
        $capturedRequest = null;
        $capturedHandler = null;
        
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse());

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($request, $handler) use (&$capturedRequest, &$capturedHandler) {
                $capturedRequest = $request;
                $capturedHandler = $handler;
                return $handler->handle($request);
            });

        $handler = new MiddlewareRequestHandler($middleware, $nextHandler);
        $originalRequest = new ServerRequest('GET', '/');
        
        $handler->handle($originalRequest);
        
        $this->assertSame($originalRequest, $capturedRequest);
        $this->assertSame($nextHandler, $capturedHandler);
    }
}