<?php

declare(strict_types=1);

namespace CitiesRpg\Tests;

use PCF\Addendum\Http\Request;
use PCF\Addendum\Http\RequestFactory;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class RequestTest extends TestCase
{
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

    public function testJsonMethodReturnsEmptyArrayForInvalidJson(): void
    {
        $serverRequest = new ServerRequest('POST', '/', [], 'invalid json');
        
        $request = $this->requestFactory->create($serverRequest);
        
        $this->assertSame([], $request->json());
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