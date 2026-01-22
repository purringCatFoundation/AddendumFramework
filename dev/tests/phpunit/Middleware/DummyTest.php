<?php
declare(strict_types=1);

namespace CitiesRpg\Tests\Middleware;

use PCF\Addendum\Http\Middleware\Dummy;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DummyTest extends TestCase
{
    public function testAddsHeaderAndAttributeWithMockedHandler(): void
    {
        $capturedRequest = null;
        $expectedResponse = new PsrResponse(200);
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($request) use (&$capturedRequest, $expectedResponse) {
                $capturedRequest = $request;
                return $expectedResponse;
            });

        $middleware = new Dummy(['attr' => 'test-attribute', 'header' => 'test-header']);
        $request = new ServerRequest('GET', '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertSame('test-attribute', $capturedRequest->getAttribute('dummy'));
        $this->assertSame('test-header', $response->getHeaderLine('X-Dummy'));
    }

    public function testAddsSimpleStringAttribute(): void
    {
        $attributeValue = 'simple';
        $capturedRequest = null;
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($request) use (&$capturedRequest) {
                $capturedRequest = $request;
                return new PsrResponse();
            });

        $middleware = new Dummy(['attr' => $attributeValue, 'header' => 'header-value']);
        $request = new ServerRequest('GET', '/');
        
        $middleware->process($request, $handler);
        
        $this->assertSame($attributeValue, $capturedRequest->getAttribute('dummy'));
    }

    public function testAddsEmptyStringAttribute(): void
    {
        $attributeValue = '';
        $capturedRequest = null;
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($request) use (&$capturedRequest) {
                $capturedRequest = $request;
                return new PsrResponse();
            });

        $middleware = new Dummy(['attr' => $attributeValue, 'header' => 'header-value']);
        $request = new ServerRequest('GET', '/');
        
        $middleware->process($request, $handler);
        
        $this->assertSame($attributeValue, $capturedRequest->getAttribute('dummy'));
    }

    public function testAddsAttributeWithSpaces(): void
    {
        $attributeValue = 'value with spaces';
        $capturedRequest = null;
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($request) use (&$capturedRequest) {
                $capturedRequest = $request;
                return new PsrResponse();
            });

        $middleware = new Dummy(['attr' => $attributeValue, 'header' => 'header-value']);
        $request = new ServerRequest('GET', '/');
        
        $middleware->process($request, $handler);
        
        $this->assertSame($attributeValue, $capturedRequest->getAttribute('dummy'));
    }

    public function testAddsAttributeWithSpecialCharacters(): void
    {
        $attributeValue = 'value@#$%^&*()';
        $capturedRequest = null;
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($request) use (&$capturedRequest) {
                $capturedRequest = $request;
                return new PsrResponse();
            });

        $middleware = new Dummy(['attr' => $attributeValue, 'header' => 'header-value']);
        $request = new ServerRequest('GET', '/');
        
        $middleware->process($request, $handler);
        
        $this->assertSame($attributeValue, $capturedRequest->getAttribute('dummy'));
    }

    public function testAddsNumericStringAttribute(): void
    {
        $attributeValue = '12345';
        $capturedRequest = null;
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($request) use (&$capturedRequest) {
                $capturedRequest = $request;
                return new PsrResponse();
            });

        $middleware = new Dummy(['attr' => $attributeValue, 'header' => 'header-value']);
        $request = new ServerRequest('GET', '/');
        
        $middleware->process($request, $handler);
        
        $this->assertSame($attributeValue, $capturedRequest->getAttribute('dummy'));
    }

    public function testAddsUnicodeAttribute(): void
    {
        $attributeValue = 'πάπα';
        $capturedRequest = null;
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($request) use (&$capturedRequest) {
                $capturedRequest = $request;
                return new PsrResponse();
            });

        $middleware = new Dummy(['attr' => $attributeValue, 'header' => 'header-value']);
        $request = new ServerRequest('GET', '/');
        
        $middleware->process($request, $handler);
        
        $this->assertSame($attributeValue, $capturedRequest->getAttribute('dummy'));
    }

    public function testAddsSimpleHeader(): void
    {
        $headerValue = 'ok';
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse());

        $middleware = new Dummy(['attr' => 'attr', 'header' => $headerValue]);
        $request = new ServerRequest('GET', '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertSame($headerValue, $response->getHeaderLine('X-Dummy'));
    }

    public function testAddsEmptyHeader(): void
    {
        $headerValue = '';
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse());

        $middleware = new Dummy(['attr' => 'attr', 'header' => $headerValue]);
        $request = new ServerRequest('GET', '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertSame($headerValue, $response->getHeaderLine('X-Dummy'));
    }

    public function testAddsHeaderWithSpaces(): void
    {
        $headerValue = 'header value';
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse());

        $middleware = new Dummy(['attr' => 'attr', 'header' => $headerValue]);
        $request = new ServerRequest('GET', '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertSame($headerValue, $response->getHeaderLine('X-Dummy'));
    }

    public function testAddsNumericHeader(): void
    {
        $headerValue = '12345';
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse());

        $middleware = new Dummy(['attr' => 'attr', 'header' => $headerValue]);
        $request = new ServerRequest('GET', '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertSame($headerValue, $response->getHeaderLine('X-Dummy'));
    }

    public function testAddsHeaderWithSpecialChars(): void
    {
        $headerValue = 'value-with-dashes';
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse());

        $middleware = new Dummy(['attr' => 'attr', 'header' => $headerValue]);
        $request = new ServerRequest('GET', '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertSame($headerValue, $response->getHeaderLine('X-Dummy'));
    }

    public function testHandlerIsCalledOnce(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse());

        $middleware = new Dummy(['attr' => 'attr', 'header' => 'header']);
        $request = new ServerRequest('GET', '/');
        
        $middleware->process($request, $handler);
    }

    public function testRequestIsModifiedBeforePassingToHandler(): void
    {
        $originalRequest = new ServerRequest('GET', '/');
        $capturedRequest = null;
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($request) use (&$capturedRequest) {
                $capturedRequest = $request;
                return new PsrResponse();
            });

        $middleware = new Dummy(['attr' => 'test-attr', 'header' => 'test-header']);
        
        $middleware->process($originalRequest, $handler);
        
        $this->assertNotSame($originalRequest, $capturedRequest);
        $this->assertSame('test-attr', $capturedRequest->getAttribute('dummy'));
        $this->assertNull($originalRequest->getAttribute('dummy'));
    }

    public function testResponseIsModifiedAfterHandlerReturns(): void
    {
        $originalResponse = new PsrResponse(201, ['Content-Type' => 'application/json']);
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($originalResponse);

        $middleware = new Dummy(['attr' => 'attr', 'header' => 'custom-header-value']);
        $request = new ServerRequest('GET', '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertNotSame($originalResponse, $response);
        $this->assertSame('custom-header-value', $response->getHeaderLine('X-Dummy'));
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testPreservesOriginalResponseProperties(): void
    {
        $originalResponse = new PsrResponse(
            418, 
            ['Content-Type' => 'text/plain', 'Custom-Header' => 'custom-value'], 
            'I am a teapot'
        );
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($originalResponse);

        $middleware = new Dummy(['attr' => 'attr', 'header' => 'dummy-header']);
        $request = new ServerRequest('GET', '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertSame(418, $response->getStatusCode());
        $this->assertSame('text/plain', $response->getHeaderLine('Content-Type'));
        $this->assertSame('custom-value', $response->getHeaderLine('Custom-Header'));
        $this->assertSame('dummy-header', $response->getHeaderLine('X-Dummy'));
        $this->assertSame('I am a teapot', (string) $response->getBody());
    }

    public function testWorksWithGetMethod(): void
    {
        $method = 'GET';
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse());

        $middleware = new Dummy(['attr' => 'attr', 'header' => 'header']);
        $request = new ServerRequest($method, '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertSame('header', $response->getHeaderLine('X-Dummy'));
    }

    public function testWorksWithPostMethod(): void
    {
        $method = 'POST';
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse());

        $middleware = new Dummy(['attr' => 'attr', 'header' => 'header']);
        $request = new ServerRequest($method, '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertSame('header', $response->getHeaderLine('X-Dummy'));
    }

    public function testWorksWithPutMethod(): void
    {
        $method = 'PUT';
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse());

        $middleware = new Dummy(['attr' => 'attr', 'header' => 'header']);
        $request = new ServerRequest($method, '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertSame('header', $response->getHeaderLine('X-Dummy'));
    }

    public function testWorksWithDeleteMethod(): void
    {
        $method = 'DELETE';
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse());

        $middleware = new Dummy(['attr' => 'attr', 'header' => 'header']);
        $request = new ServerRequest($method, '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertSame('header', $response->getHeaderLine('X-Dummy'));
    }

    public function testWorksWithPatchMethod(): void
    {
        $method = 'PATCH';
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse());

        $middleware = new Dummy(['attr' => 'attr', 'header' => 'header']);
        $request = new ServerRequest($method, '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertSame('header', $response->getHeaderLine('X-Dummy'));
    }

    public function testWorksWithOptionsMethod(): void
    {
        $method = 'OPTIONS';
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse());

        $middleware = new Dummy(['attr' => 'attr', 'header' => 'header']);
        $request = new ServerRequest($method, '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertSame('header', $response->getHeaderLine('X-Dummy'));
    }

    public function testWorksWithHeadMethod(): void
    {
        $method = 'HEAD';
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse());

        $middleware = new Dummy(['attr' => 'attr', 'header' => 'header']);
        $request = new ServerRequest($method, '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertSame('header', $response->getHeaderLine('X-Dummy'));
    }

    public function testMiddlewareWithoutAttributeOption(): void
    {
        $capturedRequest = null;
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($request) use (&$capturedRequest) {
                $capturedRequest = $request;
                return new PsrResponse();
            });

        $middleware = new Dummy(['header' => 'only-header']);
        $request = new ServerRequest('GET', '/');
        
        $middleware->process($request, $handler);
        
        $this->assertNull($capturedRequest->getAttribute('dummy'));
    }

    public function testMiddlewareWithoutHeaderOption(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new PsrResponse());

        $middleware = new Dummy(['attr' => 'only-attr']);
        $request = new ServerRequest('GET', '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertEmpty($response->getHeaderLine('X-Dummy'));
    }

    public function testMiddlewareWithEmptyOptions(): void
    {
        $capturedRequest = null;
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($request) use (&$capturedRequest) {
                $capturedRequest = $request;
                return new PsrResponse();
            });

        $middleware = new Dummy([]);
        $request = new ServerRequest('GET', '/');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertNull($capturedRequest->getAttribute('dummy'));
        $this->assertEmpty($response->getHeaderLine('X-Dummy'));
    }
}