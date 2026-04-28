<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Action;

use PCF\Addendum\Action\User\GetUserAction;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Response\User\ProfileResponse;
use PHPUnit\Framework\TestCase;

final class GetUserActionTest extends TestCase
{
    public function testUsesRouteUserUuidWhenPresent(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->with('userUuid')
            ->willReturn('route-user-uuid');

        $response = (new GetUserAction())($request);

        $this->assertInstanceOf(ProfileResponse::class, $response);
        $this->assertSame(['uuid' => 'route-user-uuid'], $response->jsonSerialize());
    }

    public function testFallsBackToAuthenticatedUserUuid(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['userUuid', null, null],
                ['user_uuid', null, 'authenticated-user-uuid'],
            ]);

        $response = (new GetUserAction())($request);

        $this->assertSame(['uuid' => 'authenticated-user-uuid'], $response->jsonSerialize());
    }
}
