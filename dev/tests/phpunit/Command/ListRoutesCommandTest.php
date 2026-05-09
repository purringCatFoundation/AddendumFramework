<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Command;

use PCF\Addendum\Attribute\AccessControl;
use PCF\Addendum\Attribute\RateLimit;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Auth\Session;
use PCF\Addendum\Command\ListRoutesCommand;
use PCF\Addendum\Guardian\AccessControlGuardianInterface;
use PCF\Addendum\Http\Cache\ResourcePolicyCollection;
use PCF\Addendum\Http\Middleware\ClassAccessControlGuardianDefinition;
use PCF\Addendum\Http\Middleware\Dummy;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RegisteredRoute;
use PCF\Addendum\Http\RouteCollection;
use PCF\Addendum\Http\RouteMiddleware;
use PCF\Addendum\Http\Router;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ListRoutesCommandTest extends TestCase
{
    public function testFailsWhenNoRoutesAreRegistered(): void
    {
        $tester = new CommandTester(new ListRoutesCommand(new Router(new RouteCollection())));

        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('No routes registered', $tester->getDisplay());
    }

    public function testListsDetailedRoutesWithFilters(): void
    {
        $tester = new CommandTester(new ListRoutesCommand(new Router($this->routes())));

        $tester->execute([
            '--detailed' => true,
            '--method' => 'GET',
            '--path' => '/admin/*',
        ]);

        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('GET', $display);
        self::assertStringContainsString('/admin/:id', $display);
        self::assertStringContainsString('Action: ListRoutesCommandFixtureAction', $display);
        self::assertStringContainsString('Guardians: ListRoutesCommandFixtureGuardian', $display);
        self::assertStringContainsString('Rate Limit: 3 requests per 60s (user)', $display);
        self::assertStringContainsString('Dummy', $display);
        self::assertStringContainsString('enabled:', $display);
        self::assertStringContainsString('true', $display);
        self::assertStringContainsString('config:', $display);
        self::assertStringContainsString('{"level":"debug"}', $display);
        self::assertStringContainsString('Total routes: 1', $display);
    }

    public function testPathFilterCanReturnNoMatchingRoutes(): void
    {
        $tester = new CommandTester(new ListRoutesCommand(new Router($this->routes())));

        $tester->execute(['--path' => '/missing/*']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Total routes: 0', $tester->getDisplay());
    }

    private function routes(): RouteCollection
    {
        $routes = new RouteCollection();
        $routes->addRoute('GET', new RegisteredRoute(
            '#^/admin/(?P<id>[^/]+)$#',
            ListRoutesCommandFixtureAction::class,
            [new RouteMiddleware(Dummy::class, new MiddlewareOptions([
                'enabled' => true,
                'config' => ['level' => 'debug'],
            ]))],
            ResourcePolicyCollection::fromArray([]),
            '/admin/:id'
        ));
        $routes->addRoute('POST', new RegisteredRoute(
            '#^/admin$#',
            ListRoutesCommandFixtureAction::class,
            [],
            ResourcePolicyCollection::fromArray([]),
            '/admin'
        ));

        return $routes;
    }
}

#[Route('/admin/:id', 'GET')]
#[AccessControl(new ClassAccessControlGuardianDefinition(ListRoutesCommandFixtureGuardian::class))]
#[RateLimit(maxAttempts: 3, windowSeconds: 60)]
final class ListRoutesCommandFixtureAction
{
}

final class ListRoutesCommandFixtureGuardian implements AccessControlGuardianInterface
{
    public function authorize(ServerRequestInterface $request, Session $session): bool
    {
        return true;
    }

}
