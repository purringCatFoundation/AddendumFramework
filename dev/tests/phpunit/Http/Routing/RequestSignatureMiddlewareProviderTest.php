<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http\Routing;

use PCF\Addendum\Action\GetHelloAction;
use PCF\Addendum\Attribute\AccessControl;
use PCF\Addendum\Attribute\Middleware;
use PCF\Addendum\Guardian\RequiresAuthGuardian;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Http\Middleware\ClassAccessControlGuardianDefinition;
use PCF\Addendum\Http\Middleware\RequestSignature;
use PCF\Addendum\Http\Routing\RequestSignatureMiddlewareProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RequestSignatureMiddlewareProviderTest extends TestCase
{
    public function testDoesNotProvideRequestSignatureByDefault(): void
    {
        $provider = new RequestSignatureMiddlewareProvider();

        $middlewares = $provider->provide(new ReflectionClass(PublicRequestSignatureFixtureAction::class));

        $this->assertTrue($middlewares->isEmpty());
    }

    public function testProvidesRequestSignatureWhenAuthMiddlewareIsDeclared(): void
    {
        $provider = new RequestSignatureMiddlewareProvider();

        $middlewares = $provider->provide(new ReflectionClass(AuthRequestSignatureFixtureAction::class));

        $this->assertCount(1, $middlewares);
        $this->assertSame(RequestSignature::class, $middlewares[0]->getClass());
    }

    public function testProvidesRequestSignatureWhenAccessControlRequiresAuth(): void
    {
        $provider = new RequestSignatureMiddlewareProvider();

        $middlewares = $provider->provide(new ReflectionClass(AccessControlledRequestSignatureFixtureAction::class));

        $this->assertCount(1, $middlewares);
        $this->assertSame(RequestSignature::class, $middlewares[0]->getClass());
    }

    public function testGetHelloActionDoesNotRequireRequestSignature(): void
    {
        $provider = new RequestSignatureMiddlewareProvider();

        $middlewares = $provider->provide(new ReflectionClass(GetHelloAction::class));

        $this->assertTrue($middlewares->isEmpty());
    }
}

final class PublicRequestSignatureFixtureAction
{
}

#[Middleware(Auth::class)]
final class AuthRequestSignatureFixtureAction
{
}

#[AccessControl(new ClassAccessControlGuardianDefinition(RequiresAuthGuardian::class))]
final class AccessControlledRequestSignatureFixtureAction
{
}
