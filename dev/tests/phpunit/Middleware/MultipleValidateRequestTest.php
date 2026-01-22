<?php
declare(strict_types=1);

namespace CitiesRpg\Tests\Middleware;

use PCF\Addendum\Http\Middleware\ValidateRequestAttribute;
use PCF\Addendum\Attribute\ValidateRequest;
use PCF\Addendum\Validation\Rules\Required;
use PCF\Addendum\Validation\Rules\Email;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

// Test action class with multiple ValidateRequest attributes
#[ValidateRequest('email', new Required(), new Email())]
#[ValidateRequest('password', new Required())]
class TestMultipleValidationAction 
{
    public function __invoke() {
        return 'test';
    }
}

final class MultipleValidateRequestTest extends TestCase
{
    public function testMultipleValidationAttributesWithValidData(): void
    {
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $expectedResponse = new PsrResponse(200);
        $nextHandler->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $middleware = new ValidateRequestAttribute(TestMultipleValidationAction::class);
        $request = new ServerRequest('POST', '/', ['Content-Type' => 'application/json'], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]));
        
        $response = $middleware->process($request, $nextHandler);
        
        $this->assertSame($expectedResponse, $response);
    }

    public function testMultipleValidationAttributesWithInvalidEmail(): void
    {
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler->expects($this->never())
            ->method('handle');

        $middleware = new ValidateRequestAttribute(TestMultipleValidationAction::class);
        $request = new ServerRequest('POST', '/', ['Content-Type' => 'application/json'], json_encode([
            'email' => 'invalid-email',
            'password' => 'password123'
        ]));
        
        $response = $middleware->process($request, $nextHandler);
        
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('email', $body['errors']);
        $this->assertContains('Field must be a valid email address', $body['errors']['email']);
    }

    public function testMultipleValidationAttributesWithMissingFields(): void
    {
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler->expects($this->never())
            ->method('handle');

        $middleware = new ValidateRequestAttribute(TestMultipleValidationAction::class);
        $request = new ServerRequest('POST', '/', ['Content-Type' => 'application/json'], json_encode([]));
        
        $response = $middleware->process($request, $nextHandler);
        
        $this->assertSame(400, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('email', $body['errors']);
        $this->assertArrayHasKey('password', $body['errors']);
        $this->assertContains('Field is required', $body['errors']['email']);
        $this->assertContains('Field is required', $body['errors']['password']);
    }
}