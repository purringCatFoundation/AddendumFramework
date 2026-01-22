<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Action;

use PCF\Addendum\Action\User\PostRefreshSessionAction;
use PCF\Addendum\Auth\AuthService;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Response\User\RefreshResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetRefreshSessionActionTest extends TestCase
{
    private AuthService&MockObject $mockAuthService;
    private PostRefreshSessionAction $action;

    protected function setUp(): void
    {
        $this->mockAuthService = $this->createMock(AuthService::class);
        $this->action = new PostRefreshSessionAction($this->mockAuthService);
    }

    public function testInvokeWithValidRefreshToken(): void
    {
        $refreshToken = 'valid-refresh-token';
        $fingerprint = 'test-fingerprint';
        $newAccessToken = 'new-access-token';
        $newRefreshToken = 'new-refresh-token';

        $mockRequest = $this->createMock(Request::class);
        $mockRequest
            ->expects($this->once())
            ->method('get')
            ->with('jwt_token')
            ->willReturn($refreshToken);

        $mockRequest
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('X-Request-Fingerprint')
            ->willReturn($fingerprint);

        $this->mockAuthService
            ->expects($this->once())
            ->method('refresh')
            ->with($refreshToken, $fingerprint)
            ->willReturn([
                'access_token' => $newAccessToken,
                'refresh_token' => $newRefreshToken
            ]);

        $response = ($this->action)($mockRequest);

        $this->assertInstanceOf(RefreshResponse::class, $response);
    }

    public function testInvokePassesTokenToAuthService(): void
    {
        $refreshToken = 'specific-token-123';
        $fingerprint = 'fingerprint-456';

        $mockRequest = $this->createMock(Request::class);
        $mockRequest
            ->expects($this->once())
            ->method('get')
            ->with('jwt_token')
            ->willReturn($refreshToken);

        $mockRequest
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('X-Request-Fingerprint')
            ->willReturn($fingerprint);

        $this->mockAuthService
            ->expects($this->once())
            ->method('refresh')
            ->with($refreshToken, $fingerprint)
            ->willReturn([
                'access_token' => 'token',
                'refresh_token' => 'refresh'
            ]);

        ($this->action)($mockRequest);
    }
}
