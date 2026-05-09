<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Middleware;

use Ds\Map;
use PCF\Addendum\Attribute\ValidateRequest;
use PCF\Addendum\Http\Middleware\ValidateRequestAttribute;
use PCF\Addendum\Validation\AbstractRequestValidator;
use PCF\Addendum\Validation\RequestAttributeProviderValidatorInterface;
use PCF\Addendum\Validation\RequestFieldSource;
use PCF\Addendum\Validation\RequestValidationPlan;
use PCF\Addendum\Validation\RequestValidationPlanRule;
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
        $middleware = $this->middleware($this->rule('X-Custom-Header', ValidateRequest::SOURCE_HEADER));

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
        $middleware = $this->middleware($this->rule('page', ValidateRequest::SOURCE_QUERY));

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
        $middleware = $this->middleware($this->rule('name', ValidateRequest::SOURCE_BODY));

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
        $middleware = $this->middleware(
            $this->rule('Authorization', ValidateRequest::SOURCE_HEADER),
            $this->rule('page', ValidateRequest::SOURCE_QUERY),
            $this->rule('name', ValidateRequest::SOURCE_BODY)
        );
        
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
        $middleware = $this->middleware($this->rule('X-Custom-Header', ValidateRequest::SOURCE_HEADER));
        
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

    public function testNoValidationRulesPassesThrough(): void
    {
        $middleware = new ValidateRequestAttribute();
        $expectedResponse = new PsrResponse(204);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expectedResponse);

        $response = $middleware->process(new ServerRequest('GET', '/test'), $handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testMalformedJsonReturnsBadRequest(): void
    {
        $middleware = $this->middleware($this->rule('name', ValidateRequest::SOURCE_BODY));
        $request = new ServerRequest('POST', '/test', ['Content-Type' => 'application/json'], '{invalid');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);
        $body = json_decode((string) $response->getBody(), true);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Malformed JSON request body', $body['error']);
    }

    public function testParsedBodyIsUsedForNonJsonRequests(): void
    {
        $middleware = $this->middleware($this->rule('name', ValidateRequest::SOURCE_BODY));
        $request = (new ServerRequest('POST', '/test'))->withParsedBody(['name' => 'Pawel']);
        $expectedResponse = new PsrResponse(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expectedResponse);

        $response = $middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testJwtHeaderValueIsStoredOnRequestWhenValidatorPasses(): void
    {
        $middleware = $this->middleware(
            new RequestValidationPlanRule('jwt_token', RequestFieldSource::Header, [new ValidateRequestAttributeTestJwtToken()])
        );
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(static fn(ServerRequestInterface $request): bool => $request->getAttribute('jwt_token') === 'token-value'))
            ->willReturn(new PsrResponse(200));

        $request = (new ServerRequest('GET', '/test'))->withHeader('Authorization', 'Bearer token-value');
        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    private function middleware(RequestValidationPlanRule ...$rules): ValidateRequestAttribute
    {
        return new ValidateRequestAttribute(new RequestValidationPlan($rules));
    }

    private function rule(string $fieldName, string $source): RequestValidationPlanRule
    {
        return new RequestValidationPlanRule($fieldName, RequestFieldSource::fromString($source), [new Required()]);
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

final class ValidateRequestAttributeTestJwtToken extends AbstractRequestValidator implements RequestAttributeProviderValidatorInterface
{
    public function validate(mixed $value): ?string
    {
        return null;
    }

    public function requestAttributes(mixed $value): Map
    {
        return new Map(['jwt_token' => trim((string) $value)]);
    }
}
