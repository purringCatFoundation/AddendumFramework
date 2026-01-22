<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Middleware;

use PCF\Addendum\Attribute\ValidateRequest;
use PCF\Addendum\Http\Middleware\ValidateRequestAttribute;
use PCF\Addendum\Validation\Rules\Required;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ValidateRequestAttributeExtendedTest extends TestCase
{
    public function testValidateHeaderField(): void
    {
        $actionClass = TestActionWithHeaderValidation::class;
        $middleware = new ValidateRequestAttribute($actionClass);

        $request = new ServerRequest('GET', '/test');
        // Don't set the header at all so it's missing

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);

        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('X-Custom-Header', $body['errors']);
    }

    public function testValidateQueryParameter(): void
    {
        $actionClass = TestActionWithQueryValidation::class;
        $middleware = new ValidateRequestAttribute($actionClass);

        $request = new ServerRequest('GET', '/test');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);

        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('page', $body['errors']);
    }

    public function testValidateBodyField(): void
    {
        $actionClass = TestActionWithBodyValidation::class;
        $middleware = new ValidateRequestAttribute($actionClass);

        $request = new ServerRequest('POST', '/test');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody(\GuzzleHttp\Psr7\Utils::streamFor('{}'));

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);

        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('name', $body['errors']);
    }

    public function testValidateMultipleSources(): void
    {
        $actionClass = TestActionWithMultipleValidations::class;
        $middleware = new ValidateRequestAttribute($actionClass);
        
        $request = new ServerRequest('POST', '/test');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody(\GuzzleHttp\Psr7\Utils::streamFor('{}'));
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('errors', $body);
        // All validators should fail for missing fields
        $this->assertArrayHasKey('Authorization', $body['errors']);
        $this->assertArrayHasKey('page', $body['errors']);
        $this->assertArrayHasKey('name', $body['errors']);
    }

    public function testValidRequestPassesThrough(): void
    {
        $actionClass = TestActionWithHeaderValidation::class;
        $middleware = new ValidateRequestAttribute($actionClass);
        
        $request = new ServerRequest('GET', '/test');
        $request = $request->withHeader('X-Custom-Header', 'valid-value');
        
        $expectedResponse = new PsrResponse(200);
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
               ->method('handle')
               ->with($this->isInstanceOf(ServerRequestInterface::class))
               ->willReturn($expectedResponse);
        
        $response = $middleware->process($request, $handler);
        
        $this->assertSame($expectedResponse, $response);
    }
}

// Test classes for validation
#[ValidateRequest('X-Custom-Header', new Required(), ValidateRequest::SOURCE_HEADER)]
class TestActionWithHeaderValidation
{
}

#[ValidateRequest('page', new Required(), ValidateRequest::SOURCE_QUERY)]
class TestActionWithQueryValidation
{
}

#[ValidateRequest('name', new Required(), ValidateRequest::SOURCE_BODY)]
class TestActionWithBodyValidation
{
}

#[ValidateRequest('Authorization', new Required(), ValidateRequest::SOURCE_HEADER)]
#[ValidateRequest('page', new Required(), ValidateRequest::SOURCE_QUERY)]
#[ValidateRequest('name', new Required(), ValidateRequest::SOURCE_BODY)]
class TestActionWithMultipleValidations
{
}