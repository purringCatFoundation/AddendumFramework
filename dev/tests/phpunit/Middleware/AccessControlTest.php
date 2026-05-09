<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Middleware;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Auth\Session;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Exception\AuthorizationError;
use PCF\Addendum\Exception\PermissionDenied;
use PCF\Addendum\Guardian\AccessControlGuardianInterface;
use PCF\Addendum\Http\Middleware\AccessControl;
use PCF\Addendum\Http\Middleware\AccessControlGuardianCollection;
use PCF\Addendum\Http\Middleware\AccessControlGuardianDefinitionInterface;
use PCF\Addendum\Http\Middleware\ClassAccessControlGuardianDefinition;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AccessControlTest extends TestCase
{
    public function testPassesThroughWhenNoGuardiansAreConfigured(): void
    {
        $handler = $this->handler();
        $handler->expects(self::once())->method('handle')->willReturn(new Response(204));

        $response = new AccessControl()->process(new ServerRequest('GET', '/'), $handler);

        self::assertSame(204, $response->getStatusCode());
    }

    public function testMissingSessionReturnsUnauthorized(): void
    {
        $handler = $this->handler();
        $handler->expects(self::never())->method('handle');

        $response = new AccessControl($this->guardians($this->classGuardian(AccessControlAllowGuardian::class)))->process(new ServerRequest('GET', '/'), $handler);
        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('Session information not found', $payload['message']);
    }

    public function testClassGuardianAllowsRequest(): void
    {
        $handler = $this->handler();
        $handler->expects(self::once())->method('handle')->willReturn(new Response(200));

        $response = new AccessControl($this->guardians($this->classGuardian(AccessControlAllowGuardian::class)))->process($this->requestWithSession(), $handler);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testPermissionDeniedGuardianReturnsForbidden(): void
    {
        $handler = $this->handler();
        $handler->expects(self::never())->method('handle');

        $response = new AccessControl($this->guardians($this->classGuardian(AccessControlPermissionDeniedGuardian::class)))->process($this->requestWithSession(), $handler);
        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(403, $response->getStatusCode());
        self::assertSame('denied', $payload['message']);
    }

    public function testAuthorizationErrorGuardianReturnsUnauthorized(): void
    {
        $handler = $this->handler();
        $handler->expects(self::never())->method('handle');

        $response = new AccessControl($this->guardians($this->classGuardian(AccessControlAuthorizationErrorGuardian::class)))->process($this->requestWithSession(), $handler);
        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('auth failed', $payload['message']);
    }

    public function testInvalidGuardianClassReturnsUnauthorized(): void
    {
        $handler = $this->handler();
        $handler->expects(self::never())->method('handle');

        $response = new AccessControl($this->guardians($this->classGuardian(AccessControlInvalidGuardian::class)))->process($this->requestWithSession(), $handler);
        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(401, $response->getStatusCode());
        self::assertStringContainsString('does not implement AccessControlGuardianInterface', $payload['message']);
    }

    private function requestWithSession(): ServerRequest
    {
        return (new ServerRequest('GET', '/'))->withAttribute('session', new Session('user-1', TokenType::USER));
    }

    private function handler(): RequestHandlerInterface
    {
        return $this->createMock(RequestHandlerInterface::class);
    }

    private function guardians(AccessControlGuardianDefinitionInterface ...$guardians): AccessControlGuardianCollection
    {
        return new AccessControlGuardianCollection($guardians);
    }

    /**
     * @param class-string $guardianClass
     */
    private function classGuardian(string $guardianClass): ClassAccessControlGuardianDefinition
    {
        return new ClassAccessControlGuardianDefinition($guardianClass);
    }

}

final class AccessControlAllowGuardian implements AccessControlGuardianInterface
{
    public function authorize(ServerRequestInterface $request, Session $session): bool
    {
        return true;
    }
}

final class AccessControlPermissionDeniedGuardian implements AccessControlGuardianInterface
{
    public function authorize(ServerRequestInterface $request, Session $session): bool
    {
        throw new PermissionDenied('denied');
    }
}

final class AccessControlAuthorizationErrorGuardian implements AccessControlGuardianInterface
{
    public function authorize(ServerRequestInterface $request, Session $session): bool
    {
        throw new AuthorizationError('auth failed');
    }
}

final class AccessControlInvalidGuardian
{
}
