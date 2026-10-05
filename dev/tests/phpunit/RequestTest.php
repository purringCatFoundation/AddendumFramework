<?php

declare(strict_types=1);

namespace CitiesRpg\Tests;

use PCF\Addendum\Http\Request;
use PCF\Addendum\Http\RequestFactory;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class RequestTest extends TestCase
{
    #[DataProvider('requestMutations')]
    public function testMutationsPreserveWrapperAndLeaveOriginalUnchanged(
        callable $mutate,
        callable $read,
        mixed $expected
    ): void {
        $original = new RequestSubclassFixture(
            new ServerRequest('GET', 'https://example.com/original', ['X-Test' => 'before'], 'original body')
                ->withAttribute('userUuid', 'user-123')
                ->withQueryParams(['page' => '2'])
        );
        $originalValue = $read($original);

        $modified = $mutate($original);

        self::assertInstanceOf(RequestSubclassFixture::class, $modified);
        self::assertNotSame($original, $modified);
        self::assertSame($expected, $read($modified));
        self::assertSame($originalValue, $read($original));
        self::assertSame('user-123', $modified->get('userUuid'));
        self::assertSame('2', $modified->get('page'));
    }

    public static function requestMutations(): iterable
    {
        yield 'target' => [
            static fn(Request $request) => $request->withRequestTarget('/changed'),
            static fn(Request $request) => $request->getRequestTarget(),
            '/changed',
        ];
        yield 'method' => [
            static fn(Request $request) => $request->withMethod('POST'),
            static fn(Request $request) => $request->getMethod(),
            'POST',
        ];
        yield 'uri' => [
            static fn(Request $request) => $request->withUri(new Uri('https://example.org/changed')),
            static fn(Request $request) => (string) $request->getUri(),
            'https://example.org/changed',
        ];
        yield 'protocol' => [
            static fn(Request $request) => $request->withProtocolVersion('2.0'),
            static fn(Request $request) => $request->getProtocolVersion(),
            '2.0',
        ];
        yield 'header' => [
            static fn(Request $request) => $request->withHeader('X-Test', 'after'),
            static fn(Request $request) => $request->getHeader('X-Test'),
            ['after'],
        ];
        yield 'added header' => [
            static fn(Request $request) => $request->withAddedHeader('X-Test', 'after'),
            static fn(Request $request) => $request->getHeader('X-Test'),
            ['before', 'after'],
        ];
        yield 'removed header' => [
            static fn(Request $request) => $request->withoutHeader('X-Test'),
            static fn(Request $request) => $request->getHeader('X-Test'),
            [],
        ];
        yield 'body' => [
            static fn(Request $request) => $request->withBody(Utils::streamFor('changed body')),
            static fn(Request $request) => (string) $request->getBody(),
            'changed body',
        ];
    }

    private RequestFactory $requestFactory;

    protected function setUp(): void
    {
        $this->requestFactory = new RequestFactory();
    }

    public function testRetrievesAttributesAndQueryParams(): void
    {
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        
        // Mock getAttribute calls - first call returns null, then 'bar', then nulls for missing
        $serverRequest->expects($this->exactly(4))
            ->method('getAttribute')
            ->willReturnMap([
                ['foo', null, 'bar'],     // First call for 'foo' returns 'bar'
                ['baz', null, null],      // Call for 'baz' returns null (will check query params)
                ['missing', null, null],  // Call for 'missing' returns null
                ['missing', null, null],  // Second call for 'missing' returns null
            ]);
        
        // Mock getQueryParams calls - returns the query array when needed
        $serverRequest->expects($this->exactly(3))
            ->method('getQueryParams')
            ->willReturn(['baz' => 'qux']);

        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame('bar', $request->get('foo'));       // From attribute
        $this->assertSame('qux', $request->get('baz'));       // From query params
        $this->assertNull($request->get('missing'));          // Not found
        $this->assertSame('default', $request->get('missing', 'default')); // Default value
    }

    public function testRetrievesStringParameterType(): void
    {
        $key = 'name';
        $value = 'John';
        $expected = 'John';
        
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->with($key, null)
            ->willReturn($value);
        
        // Only call getQueryParams if attribute returns null
        if ($value === null) {
            $serverRequest->expects($this->once())
                ->method('getQueryParams')
                ->willReturn([]);
        } else {
            $serverRequest->expects($this->never())
                ->method('getQueryParams');
        }

        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($expected, $request->get($key));
    }

    public function testRetrievesIntegerParameterType(): void
    {
        $key = 'id';
        $value = 123;
        $expected = 123;
        
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->with($key, null)
            ->willReturn($value);
        
        // Only call getQueryParams if attribute returns null
        if ($value === null) {
            $serverRequest->expects($this->once())
                ->method('getQueryParams')
                ->willReturn([]);
        } else {
            $serverRequest->expects($this->never())
                ->method('getQueryParams');
        }

        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($expected, $request->get($key));
    }

    public function testRetrievesBooleanTrueParameterType(): void
    {
        $key = 'active';
        $value = true;
        $expected = true;
        
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->with($key, null)
            ->willReturn($value);
        
        // Only call getQueryParams if attribute returns null
        if ($value === null) {
            $serverRequest->expects($this->once())
                ->method('getQueryParams')
                ->willReturn([]);
        } else {
            $serverRequest->expects($this->never())
                ->method('getQueryParams');
        }

        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($expected, $request->get($key));
    }

    public function testRetrievesBooleanFalseParameterType(): void
    {
        $key = 'active';
        $value = false;
        $expected = false;
        
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->with($key, null)
            ->willReturn($value);
        
        // Only call getQueryParams if attribute returns null
        if ($value === null) {
            $serverRequest->expects($this->once())
                ->method('getQueryParams')
                ->willReturn([]);
        } else {
            $serverRequest->expects($this->never())
                ->method('getQueryParams');
        }

        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($expected, $request->get($key));
    }

    public function testRetrievesArrayParameterType(): void
    {
        $key = 'tags';
        $value = ['php', 'test'];
        $expected = ['php', 'test'];
        
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->with($key, null)
            ->willReturn($value);
        
        // Only call getQueryParams if attribute returns null
        if ($value === null) {
            $serverRequest->expects($this->once())
                ->method('getQueryParams')
                ->willReturn([]);
        } else {
            $serverRequest->expects($this->never())
                ->method('getQueryParams');
        }

        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($expected, $request->get($key));
    }

    public function testRetrievesNullParameterType(): void
    {
        $key = 'empty';
        $value = null;
        $expected = null;
        
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->with($key, null)
            ->willReturn($value);
        
        // Only call getQueryParams if attribute returns null
        if ($value === null) {
            $serverRequest->expects($this->once())
                ->method('getQueryParams')
                ->willReturn([]);
        } else {
            $serverRequest->expects($this->never())
                ->method('getQueryParams');
        }

        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($expected, $request->get($key));
    }

    public function testAttributeHasPriorityOverQueryParams(): void
    {
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->with('param', null)
            ->willReturn('from-attribute');
        
        // Since attribute is not null, getQueryParams should not be called
        $serverRequest->expects($this->never())
            ->method('getQueryParams');

        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame('from-attribute', $request->get('param'));
    }

    public function testQueryParamUsedWhenAttributeMissing(): void
    {
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->with('param', null)
            ->willReturn(null);
        
        $serverRequest->expects($this->once())
            ->method('getQueryParams')
            ->willReturn(['param' => 'from-query', 'other' => 'value']);

        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame('from-query', $request->get('param'));
    }

    public function testDefaultValueUsedWhenBothMissing(): void
    {
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->with('missing', null)
            ->willReturn(null);
        
        $serverRequest->expects($this->once())
            ->method('getQueryParams')
            ->willReturn([]);

        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame('default-value', $request->get('missing', 'default-value'));
    }

    public function testStringDefaultValue(): void
    {
        $defaultValue = 'default';
        $expected = 'default';
        
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->willReturn(null);
        
        $serverRequest->expects($this->once())
            ->method('getQueryParams')
            ->willReturn([]);

        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($expected, $request->get('missing', $defaultValue));
    }

    public function testIntegerDefaultValue(): void
    {
        $defaultValue = 42;
        $expected = 42;
        
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->willReturn(null);
        
        $serverRequest->expects($this->once())
            ->method('getQueryParams')
            ->willReturn([]);

        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($expected, $request->get('missing', $defaultValue));
    }

    public function testBooleanDefaultValue(): void
    {
        $defaultValue = true;
        $expected = true;
        
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->willReturn(null);
        
        $serverRequest->expects($this->once())
            ->method('getQueryParams')
            ->willReturn([]);

        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($expected, $request->get('missing', $defaultValue));
    }

    public function testArrayDefaultValue(): void
    {
        $defaultValue = ['a', 'b'];
        $expected = ['a', 'b'];
        
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->willReturn(null);
        
        $serverRequest->expects($this->once())
            ->method('getQueryParams')
            ->willReturn([]);

        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($expected, $request->get('missing', $defaultValue));
    }

    public function testNullDefaultValue(): void
    {
        $defaultValue = null;
        $expected = null;
        
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->willReturn(null);
        
        $serverRequest->expects($this->once())
            ->method('getQueryParams')
            ->willReturn([]);

        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($expected, $request->get('missing', $defaultValue));
    }

    public function testRequestCreationFromRealServerRequest(): void
    {
        // This test uses a real ServerRequest to ensure integration works
        $serverRequest = new ServerRequest('GET', '/')
            ->withAttribute('foo', 'bar')
            ->withQueryParams(['baz' => 'qux']);
        
        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertInstanceOf(Request::class, $request);
        $this->assertSame('bar', $request->get('foo'));
        $this->assertSame('qux', $request->get('baz'));
    }

    public function testJsonMethodReturnsDecodedBody(): void
    {
        $jsonData = ['name' => 'John', 'age' => 30];
        $serverRequest = new ServerRequest('POST', '/', [], json_encode($jsonData));
        
        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($jsonData, $request->json());
    }

    public function testJsonMethodRejectsInvalidJson(): void
    {
        $serverRequest = new ServerRequest('POST', '/', [], 'invalid json');
        
        $request = $this->requestFactory->create($serverRequest);

        $this->expectException(\PCF\Addendum\Exception\HttpException::class);
        $this->expectExceptionMessage('Malformed JSON request body');

        $request->json();
    }

    public function testRequestImplementsCorrectInterface(): void
    {
        $serverRequest = new ServerRequest('GET', '/');
        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertInstanceOf(\Psr\Http\Message\RequestInterface::class, $request);
    }

    public function testRequestPreservesGetMethod(): void
    {
        $method = 'GET';
        $serverRequest = new ServerRequest($method, '/');
        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($method, $request->getMethod());
    }

    public function testRequestPreservesPostMethod(): void
    {
        $method = 'POST';
        $serverRequest = new ServerRequest($method, '/');
        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($method, $request->getMethod());
    }

    public function testRequestPreservesPutMethod(): void
    {
        $method = 'PUT';
        $serverRequest = new ServerRequest($method, '/');
        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($method, $request->getMethod());
    }

    public function testRequestPreservesDeleteMethod(): void
    {
        $method = 'DELETE';
        $serverRequest = new ServerRequest($method, '/');
        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($method, $request->getMethod());
    }

    public function testRequestPreservesPatchMethod(): void
    {
        $method = 'PATCH';
        $serverRequest = new ServerRequest($method, '/');
        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($method, $request->getMethod());
    }

    public function testRequestPreservesOptionsMethod(): void
    {
        $method = 'OPTIONS';
        $serverRequest = new ServerRequest($method, '/');
        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($method, $request->getMethod());
    }

    public function testRequestPreservesHeadMethod(): void
    {
        $method = 'HEAD';
        $serverRequest = new ServerRequest($method, '/');
        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame($method, $request->getMethod());
    }
}

final class RequestSubclassFixture extends Request
{
}
