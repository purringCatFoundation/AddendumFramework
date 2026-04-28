<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Action;

use PCF\Addendum\Action\User\PostSessionAction;
use PCF\Addendum\Auth\AuthService;
use PCF\Addendum\Auth\TokenPair;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Response\User\LoginResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PostSessionActionTest extends TestCase
{
    private AuthService&MockObject $mockAuthService;
    private PostSessionAction $action;

    protected function setUp(): void
    {
        $this->mockAuthService = $this->createMock(AuthService::class);
        $this->action = new PostSessionAction($this->mockAuthService);
    }

    public function testInvokeWithValidCredentials(): void
    {
        $email = 'test@example.com';
        $password = 'password123';
        $fingerprint = 'test-fingerprint';
        $accessToken = 'access-token-123';
        $refreshToken = 'refresh-token-456';

        $mockRequest = $this->createMock(Request::class);
        $mockRequest
            ->expects($this->once())
            ->method('json')
            ->willReturn(['email' => $email, 'password' => $password]);

        $mockRequest
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('X-Request-Fingerprint')
            ->willReturn($fingerprint);

        $this->mockAuthService
            ->expects($this->once())
            ->method('login')
            ->with($email, $password, $fingerprint)
            ->willReturn(new TokenPair($accessToken, $refreshToken, 3600));

        $response = ($this->action)($mockRequest);

        $this->assertInstanceOf(LoginResponse::class, $response);
    }

    public function testInvokeWithMissingEmail(): void
    {
        $mockRequest = $this->createMock(Request::class);
        $mockRequest
            ->expects($this->once())
            ->method('json')
            ->willReturn(['password' => 'password123']);

        $mockRequest
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('X-Request-Fingerprint')
            ->willReturn('fingerprint');

        $this->mockAuthService
            ->expects($this->once())
            ->method('login')
            ->with('', 'password123', 'fingerprint')
            ->willReturn(new TokenPair('token', 'refresh', 3600));

        $response = ($this->action)($mockRequest);

        $this->assertInstanceOf(LoginResponse::class, $response);
    }

    public function testInvokeWithMissingPassword(): void
    {
        $mockRequest = $this->createMock(Request::class);
        $mockRequest
            ->expects($this->once())
            ->method('json')
            ->willReturn(['email' => 'test@example.com']);

        $mockRequest
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('X-Request-Fingerprint')
            ->willReturn('fingerprint');

        $this->mockAuthService
            ->expects($this->once())
            ->method('login')
            ->with('test@example.com', '', 'fingerprint')
            ->willReturn(new TokenPair('token', 'refresh', 3600));

        $response = ($this->action)($mockRequest);

        $this->assertInstanceOf(LoginResponse::class, $response);
    }

    public function testInvokeWithEmptyData(): void
    {
        $mockRequest = $this->createMock(Request::class);
        $mockRequest
            ->expects($this->once())
            ->method('json')
            ->willReturn([]);

        $mockRequest
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('X-Request-Fingerprint')
            ->willReturn('fingerprint');

        $this->mockAuthService
            ->expects($this->once())
            ->method('login')
            ->with('', '', 'fingerprint')
            ->willReturn(new TokenPair('token', 'refresh', 3600));

        $response = ($this->action)($mockRequest);

        $this->assertInstanceOf(LoginResponse::class, $response);
    }
}
