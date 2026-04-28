<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Middleware;

use PCF\Addendum\Http\Middleware\RequestSignature;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestSignatureTest extends TestCase
{
    private const JWT_SECRET = 'test-jwt-secret-for-testing';

    private RequestSignature $middleware;
    private RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->middleware = new RequestSignature(self::JWT_SECRET);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
    }

    public function testMissingTimestampHeaderReturns400(): void
    {
        $request = new ServerRequest('GET', '/test');
        $request = $request
            ->withHeader('X-Request-Fingerprint', 'fingerprint')
            ->withHeader('X-Request-Signature', 'signature');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(400, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Missing required header: X-Request-Timestamp', $body['message']);
    }

    public function testMissingFingerprintHeaderReturns400(): void
    {
        $request = new ServerRequest('GET', '/test');
        $request = $request
            ->withHeader('X-Request-Timestamp', (string) time())
            ->withHeader('X-Request-Signature', 'signature');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(400, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Missing required header: X-Request-Fingerprint', $body['message']);
    }

    public function testMissingSignatureHeaderReturns400(): void
    {
        $request = new ServerRequest('GET', '/test');
        $request = $request
            ->withHeader('X-Request-Timestamp', (string) time())
            ->withHeader('X-Request-Fingerprint', 'fingerprint');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(400, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Missing required header: X-Request-Signature', $body['message']);
    }

    public function testInvalidTimestampFormatReturns400(): void
    {
        $request = new ServerRequest('GET', '/test');
        $request = $request
            ->withHeader('X-Request-Timestamp', 'not-a-number')
            ->withHeader('X-Request-Fingerprint', 'fingerprint')
            ->withHeader('X-Request-Signature', 'signature');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(400, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Invalid timestamp format', $body['message']);
    }

    public function testExpiredTimestampReturns400(): void
    {
        $oldTimestamp = time() - 400; // More than 5 minutes ago

        $request = new ServerRequest('GET', '/test');
        $request = $request
            ->withHeader('X-Request-Timestamp', (string) $oldTimestamp)
            ->withHeader('X-Request-Fingerprint', 'fingerprint')
            ->withHeader('X-Request-Signature', 'signature');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(400, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Request timestamp outside acceptable window (5 minutes)', $body['message']);
    }

    public function testFutureTimestampReturns400(): void
    {
        $futureTimestamp = time() + 400; // More than 5 minutes in future

        $request = new ServerRequest('GET', '/test');
        $request = $request
            ->withHeader('X-Request-Timestamp', (string) $futureTimestamp)
            ->withHeader('X-Request-Fingerprint', 'fingerprint')
            ->withHeader('X-Request-Signature', 'signature');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(400, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Request timestamp outside acceptable window (5 minutes)', $body['message']);
    }

    public function testInvalidSignatureReturns403(): void
    {
        $timestamp = time();
        $fingerprint = 'test-fingerprint';

        $request = new ServerRequest('GET', '/test');
        $request = $request
            ->withHeader('X-Request-Timestamp', (string) $timestamp)
            ->withHeader('X-Request-Fingerprint', $fingerprint)
            ->withHeader('X-Request-Signature', 'invalid-signature');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(403, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Invalid request signature', $body['message']);
    }

    public function testValidSignatureForPublicEndpoint(): void
    {
        $timestamp = time();
        $fingerprint = 'test-fingerprint';
        $method = 'GET';
        $path = '/test';
        $body = '';

        $data = $timestamp . $fingerprint . $method . $path . $body;
        $signature = hash_hmac('sha256', $data, $fingerprint);

        $request = new ServerRequest($method, $path);
        $request = $request
            ->withHeader('X-Request-Timestamp', (string) $timestamp)
            ->withHeader('X-Request-Fingerprint', $fingerprint)
            ->withHeader('X-Request-Signature', $signature);

        $expectedResponse = new Response(200);
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testValidSignatureForAuthenticatedEndpoint(): void
    {
        $timestamp = time();
        $fingerprint = 'test-fingerprint';
        $fingerprintHash = sha1($fingerprint);
        $jti = 'test-jti-123';
        $method = 'GET';
        $path = '/protected';
        $bodyContent = '';

        $signingKey = hash_hmac('sha256', $jti . $fingerprintHash, self::JWT_SECRET);
        $data = $timestamp . $fingerprint . $method . $path . $bodyContent;
        $signature = hash_hmac('sha256', $data, $signingKey);

        $request = new ServerRequest($method, $path);
        $request = $request
            ->withHeader('X-Request-Timestamp', (string) $timestamp)
            ->withHeader('X-Request-Fingerprint', $fingerprint)
            ->withHeader('X-Request-Signature', $signature)
            ->withAttribute('jti', $jti)
            ->withAttribute('fingerprint_hash', $fingerprintHash);

        $expectedResponse = new Response(200);
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testFingerprintMismatchReturns403(): void
    {
        $timestamp = time();
        $fingerprint = 'test-fingerprint';
        $storedFingerprintHash = sha1('different-fingerprint'); // Token was created with different fingerprint
        $jti = 'test-jti-123';
        $method = 'GET';
        $path = '/protected';

        // Calculate signature using current fingerprint and correct signing key for the stored fingerprint
        // The signature itself will be valid, but fingerprint hash won't match
        $signingKey = hash_hmac('sha256', $jti . $storedFingerprintHash, self::JWT_SECRET);
        $data = $timestamp . $fingerprint . $method . $path . '';
        $signature = hash_hmac('sha256', $data, $signingKey);

        $request = new ServerRequest($method, $path);
        $request = $request
            ->withHeader('X-Request-Timestamp', (string) $timestamp)
            ->withHeader('X-Request-Fingerprint', $fingerprint)
            ->withHeader('X-Request-Signature', $signature)
            ->withAttribute('jti', $jti)
            ->withAttribute('fingerprint_hash', $storedFingerprintHash); // Stored fingerprint doesn't match

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(403, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Device fingerprint mismatch - token may have been stolen', $body['message']);
    }

    public function testTimestampWithinToleranceWindow(): void
    {
        $timestamp = time() - 200; // 3+ minutes ago, but within 5 minute window
        $fingerprint = 'test-fingerprint';
        $method = 'GET';
        $path = '/test';

        $data = $timestamp . $fingerprint . $method . $path . '';
        $signature = hash_hmac('sha256', $data, $fingerprint);

        $request = new ServerRequest($method, $path);
        $request = $request
            ->withHeader('X-Request-Timestamp', (string) $timestamp)
            ->withHeader('X-Request-Fingerprint', $fingerprint)
            ->withHeader('X-Request-Signature', $signature);

        $expectedResponse = new Response(200);
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSignatureWithRequestBody(): void
    {
        $timestamp = time();
        $fingerprint = 'test-fingerprint';
        $method = 'POST';
        $path = '/test';
        $bodyContent = '{"email":"test@example.com"}';

        $data = $timestamp . $fingerprint . $method . $path . $bodyContent;
        $signature = hash_hmac('sha256', $data, $fingerprint);

        $request = new ServerRequest($method, $path, [], $bodyContent);
        $request = $request
            ->withHeader('X-Request-Timestamp', (string) $timestamp)
            ->withHeader('X-Request-Fingerprint', $fingerprint)
            ->withHeader('X-Request-Signature', $signature);

        $expectedResponse = new Response(200);
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testResponseContentTypeIsJson(): void
    {
        $request = new ServerRequest('GET', '/test');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testErrorResponseStructure(): void
    {
        $request = new ServerRequest('GET', '/test');

        $response = $this->middleware->process($request, $this->handler);

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);
        $this->assertSame('Signature Verification Failed', $body['error']);
    }
}
