<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Application;

use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Application\App;
use PCF\Addendum\Http\RegisteredRoute;
use PCF\Addendum\Http\Router;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AppTest extends TestCase
{
    public function testReturnsMethodNotAllowedWithAllowHeader(): void
    {
        $router = new AppTestRouter(null, ['GET', 'PATCH']);
        $app = new App($router, new NullLogger());

        $response = $app->handle(new ServerRequest('POST', '/v1/users/me'));

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('GET, PATCH', $response->getHeaderLine('Allow'));
    }

    public function testReturnsNotFoundWhenPathDoesNotExist(): void
    {
        $router = new AppTestRouter(null, []);
        $app = new App($router, new NullLogger());

        $response = $app->handle(new ServerRequest('GET', '/missing'));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testRejectsUnsupportedAcceptHeader(): void
    {
        $route = new RegisteredRoute('#^/test$#', AppTestAction::class, []);
        $router = new AppTestRouter($route, []);
        $app = new App($router, new NullLogger());

        $request = (new ServerRequest('GET', '/test'))->withHeader('Accept', 'text/plain');
        $response = $app->handle($request);

        $this->assertSame(406, $response->getStatusCode());
    }

    public function testRejectsUnsupportedContentTypeForJsonBody(): void
    {
        $route = new RegisteredRoute('#^/test$#', AppTestAction::class, []);
        $router = new AppTestRouter($route, []);
        $app = new App($router, new NullLogger());

        $request = (new ServerRequest('POST', '/test', [], '{"ok":true}'))
            ->withHeader('Content-Type', 'text/plain');
        $response = $app->handle($request);

        $this->assertSame(415, $response->getStatusCode());
    }
}

final class AppTestRouter extends Router
{
    /**
     * @param list<string> $allowedMethods
     */
    public function __construct(
        private readonly ?RegisteredRoute $route,
        private readonly array $allowedMethods
    ) {
    }

    public function match(\Psr\Http\Message\ServerRequestInterface $request): ?\PCF\Addendum\Http\RouteMatch
    {
        if ($this->route === null) {
            return null;
        }

        return $this->route->createMatchResult($request);
    }

    public function getAllowedMethodsForPath(string $path): array
    {
        return $this->allowedMethods;
    }
}

final class AppTestAction
{
    public function __invoke(): array
    {
        return ['ok' => true];
    }
}
