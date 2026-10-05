<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http\Routing;

use PCF\Addendum\Action\GetHelloAction;
use PCF\Addendum\Action\User\PostRefreshSessionAction;
use PCF\Addendum\Attribute\AccessControl;
use PCF\Addendum\Attribute\Middleware;
use PCF\Addendum\Guardian\RequiresAuthGuardian;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Http\Middleware\ClassAccessControlGuardianDefinition;
use PCF\Addendum\Http\Middleware\RequestSignature;
use PCF\Addendum\Http\Middleware\RefreshAuth;
use PCF\Addendum\Http\Middleware\ValidateRequestAttribute;
use PCF\Addendum\Http\Routing\MiddlewareStackBuilder;
use PCF\Addendum\Http\Routing\ActionScanner;
use PCF\Addendum\Http\Routing\CompiledRouteCollectionGenerator;
use PCF\Addendum\Http\Routing\RouteCollectionBuilder;
use PCF\Addendum\Http\Routing\RoutePatternCompiler;
use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Http\Routing\RequestSignatureMiddlewareProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RequestSignatureMiddlewareProviderTest extends TestCase
{
    public function testRefreshRouteUsesRefreshOnlyAuthenticationBeforeRequestSignature(): void
    {
        $middlewares = new MiddlewareStackBuilder()->buildStack(new ReflectionClass(PostRefreshSessionAction::class));
        $classes = [];
        foreach ($middlewares as $middleware) {
            $classes[] = $middleware->getClass();
        }

        self::assertSame(RefreshAuth::class, $classes[0]);
        self::assertSame(RequestSignature::class, $classes[1]);
        self::assertContains(ValidateRequestAttribute::class, $classes);
        self::assertNotContains(Auth::class, $classes);
    }

    public function testCompiledRefreshRoutePreservesRefreshOnlyAuthentication(): void
    {
        $routes = new RouteCollectionBuilder(
            [new ActionScanner(dirname(__DIR__, 5) . '/src/Action/User')],
            new MiddlewareStackBuilder(),
            new RoutePatternCompiler()
        )->build();
        $code = new CompiledRouteCollectionGenerator()->generate($routes);
        $factory = eval('?>' . $code);
        $compiled = $factory();
        $route = $compiled->match(new ServerRequest('POST', '/v1/session-refreshes'));

        self::assertNotNull($route);
        self::assertSame(RefreshAuth::class, $route->middlewares[0]->getClass());
        self::assertSame(RequestSignature::class, $route->middlewares[1]->getClass());
    }

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
