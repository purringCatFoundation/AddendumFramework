<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http\Routing;

use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Attribute\ResourcePolicy;
use PCF\Addendum\Auth\Session;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Guardian\AccessControlGuardianInterface;
use PCF\Addendum\Http\Cache\HttpCacheMode;
use PCF\Addendum\Http\Cache\ResourcePolicyCollection;
use PCF\Addendum\Http\Middleware\AccessControl as AccessControlMiddleware;
use PCF\Addendum\Http\Middleware\AccessControlGuardianCollection;
use PCF\Addendum\Http\Middleware\ClassAccessControlGuardianDefinition;
use PCF\Addendum\Http\Middleware\RateLimitMiddleware;
use PCF\Addendum\Http\Middleware\ValidateRequestAttribute;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RegisteredRoute;
use PCF\Addendum\Http\RouteCollection;
use PCF\Addendum\Http\RouteMiddleware;
use PCF\Addendum\Http\Routing\CompiledRouteCollectionGenerator;
use PCF\Addendum\Validation\RequestFieldSource;
use PCF\Addendum\Validation\RequestValidationConstraintInterface;
use PCF\Addendum\Validation\RequestValidationConstraintCollection;
use PCF\Addendum\Validation\RequestValidationRule;
use PCF\Addendum\Validation\RequestValidationRuleCollection;
use PCF\Addendum\Validation\Rules\JwtToken;
use PCF\Addendum\Validation\Rules\MaxLength;
use PCF\Addendum\Validation\Rules\Required;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class CompiledRouteCollectionGeneratorTest extends TestCase
{
    public function testGeneratedRoutesFileReturnsMatchingRouteCollection(): void
    {
        $routes = new RouteCollection();
        $routes->addRoute('GET', new RegisteredRoute(
            '#^/compiled/(?P<id>[^/]+)$#',
            CompiledRouteFixtureAction::class,
            [
                new RouteMiddleware(
                    RateLimitMiddleware::class,
                    new MiddlewareOptions(['limit' => 1])
                ),
                new RouteMiddleware(
                    ValidateRequestAttribute::class,
                    new MiddlewareOptions([
                        'validationRules' => new RequestValidationRuleCollection([
                            new RequestValidationRule(
                                'name',
                                RequestFieldSource::Body,
                                new RequestValidationConstraintCollection([new Required(), new MaxLength(10)])
                            ),
                            new RequestValidationRule(
                                'jwt_token',
                                RequestFieldSource::Header,
                                new RequestValidationConstraintCollection([new JwtToken(TokenType::USER_REFRESH)])
                            ),
                        ]),
                    ])
                ),
                new RouteMiddleware(
                    AccessControlMiddleware::class,
                    new MiddlewareOptions([
                        'accessControlGuardians' => new AccessControlGuardianCollection([
                            new ClassAccessControlGuardianDefinition(CompiledRouteFixtureGuardian::class),
                        ]),
                    ])
                ),
            ],
            new ResourcePolicyCollection([
                new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 60, resource: 'compiled', idAttribute: 'id'),
            ]),
            '/compiled/:id'
        ));

        $filePath = tempnam(sys_get_temp_dir(), 'compiled-routes-');
        self::assertIsString($filePath);

        $generatedCode = new CompiledRouteCollectionGenerator()->generate($routes);
        file_put_contents($filePath, $generatedCode);

        try {
            $factory = require $filePath;
            $compiledRoutes = $factory();
        } finally {
            unlink($filePath);
        }

        self::assertInstanceOf(RouteCollection::class, $compiledRoutes);
        self::assertStringNotContainsString('MiddlewareOptions(actionClass:', $generatedCode);
        self::assertStringContainsString('new \\PCF\\Addendum\\Validation\\RequestValidationRuleCollection', $generatedCode);
        self::assertStringContainsString('new \\PCF\\Addendum\\Validation\\Rules\\MaxLength(maxLength: 10)', $generatedCode);
        self::assertStringContainsString("new \\PCF\\Addendum\\Validation\\Rules\\JwtToken(requiredTokenType: 'user_refresh')", $generatedCode);
        self::assertStringContainsString('new \\PCF\\Addendum\\Http\\Middleware\\AccessControlGuardianCollection', $generatedCode);
        self::assertStringContainsString('new \\PCF\\Addendum\\Http\\Middleware\\ClassAccessControlGuardianDefinition', $generatedCode);

        $match = $compiledRoutes->match(new ServerRequest('GET', '/compiled/123'));

        self::assertNotNull($match);
        self::assertSame(CompiledRouteFixtureAction::class, $match->actionClass);
        self::assertSame('123', $match->request->getAttribute('id'));
        self::assertSame('/compiled/:id', $compiledRoutes->getRoutesForMethod('GET')[0]->path);
        self::assertSame(['compiled:123'], $match->resourcePolicies->resourceNames($match->request)->toArray());
        self::assertSame(RateLimitMiddleware::class, $match->middlewares[0]->getClass());
        self::assertSame(['limit' => 1], $match->middlewares[0]->getOptions()->additionalData->toArray());
        self::assertInstanceOf(RequestValidationRuleCollection::class, $match->middlewares[1]->getOptions()->get('validationRules'));
        self::assertInstanceOf(AccessControlGuardianCollection::class, $match->middlewares[2]->getOptions()->get('accessControlGuardians'));
    }

    public function testGeneratorRejectsValidationConstraintWithoutCompiledExporter(): void
    {
        $routes = new RouteCollection();
        $routes->addRoute('GET', new RegisteredRoute(
            '#^/custom$#',
            CompiledRouteFixtureAction::class,
            [
                new RouteMiddleware(
                    ValidateRequestAttribute::class,
                    new MiddlewareOptions([
                        'validationRules' => new RequestValidationRuleCollection([
                            new RequestValidationRule(
                                'name',
                                RequestFieldSource::Body,
                                new RequestValidationConstraintCollection([new NonExportableValidationConstraint()])
                            ),
                        ]),
                    ])
                ),
            ],
            new ResourcePolicyCollection([new ResourcePolicy()]),
            '/custom'
        ));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot compile validation constraint');

        new CompiledRouteCollectionGenerator()->generate($routes);
    }
}

final class CompiledRouteFixtureAction
{
}

final class CompiledRouteFixtureGuardian implements AccessControlGuardianInterface
{
    public function authorize(ServerRequestInterface $request, Session $session): bool
    {
        return true;
    }

}

final class NonExportableValidationConstraint implements RequestValidationConstraintInterface
{
}
