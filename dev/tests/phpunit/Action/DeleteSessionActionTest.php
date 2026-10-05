<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Action;

use PCF\Addendum\Action\User\DeleteSessionAction;
use PCF\Addendum\Auth\AuthService;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Response\NoContentResponse;
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
        $payload = new TokenPayload(
            sub: '123e4567-e89b-12d3-a456-426614174000',
            exp: 1700003600,
            jti: 'jti-1',
            iat: 1700000000,
            tokenType: TokenType::USER
        );

        $mockRequest = $this->createMock(Request::class);
        $mockRequest
            ->expects($this->once())
            ->method('get')
            ->with('token_payload')
            ->willReturn($payload);

        $this->mockAuthService
            ->expects($this->once())
            ->method('logout')
            ->with($payload, 'user_logout');

        $response = ($this->logoutAction)($mockRequest);

        $this->assertInstanceOf(NoContentResponse::class, $response);
    }

    public function testInvokeWithMissingTokenPayload(): void
    {
        $mockRequest = $this->createMock(Request::class);
        $mockRequest
            ->expects($this->once())
            ->method('get')
            ->with('token_payload')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);

        ($this->logoutAction)($mockRequest);
    }
}
