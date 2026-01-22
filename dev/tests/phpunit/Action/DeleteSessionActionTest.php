<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Action;

use PCF\Addendum\Action\User\DeleteSessionAction;
use PCF\Addendum\Auth\AuthService;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Response\User\LogoutResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeleteSessionActionTest extends TestCase
{
    private AuthService&MockObject $mockAuthService;
    private DeleteSessionAction $logoutAction;

    protected function setUp(): void
    {
        $this->mockAuthService = $this->createMock(AuthService::class);
        $this->logoutAction = new DeleteSessionAction($this->mockAuthService);
    }

    public function testInvokeWithValidUser(): void
    {
        $userUuid = '123e4567-e89b-12d3-a456-426614174000';

        $mockRequest = $this->createMock(Request::class);
        $mockRequest
            ->expects($this->once())
            ->method('get')
            ->with('user_uuid')
            ->willReturn($userUuid);

        $this->mockAuthService
            ->expects($this->once())
            ->method('logout')
            ->with($userUuid, 'user_logout');

        $response = ($this->logoutAction)($mockRequest);

        $this->assertInstanceOf(LogoutResponse::class, $response);
    }

    public function testInvokeWithNullUserUuid(): void
    {
        $mockRequest = $this->createMock(Request::class);
        $mockRequest
            ->expects($this->once())
            ->method('get')
            ->with('user_uuid')
            ->willReturn(null);

        $this->expectException(\TypeError::class);

        ($this->logoutAction)($mockRequest);
    }
}
