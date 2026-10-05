<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Auth;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Action\User\DeleteSessionAction;
use PCF\Addendum\Auth\ApplicationTokenValidator;
use PCF\Addendum\Auth\AuthRepositoryInterface;
use PCF\Addendum\Auth\AuthService;
use PCF\Addendum\Auth\JtiGenerator;
use PCF\Addendum\Auth\Jwt;
use PCF\Addendum\Auth\TokenValidationRepository;
use PCF\Addendum\Auth\UserIdentity;
use PCF\Addendum\Config\JwtConfig;
use PCF\Addendum\Exception\UnauthorizedException;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Repository\User\AdminRepositoryInterface;
use PCF\Addendum\Tests\Support\JwtKeyPair;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SessionRevocationIntegrationTest extends TestCase
{
    public function testLogoutBlocksOldAndRenewedTokensOfOneSessionButNotOtherDevices(): void
    {
        $dsn = getenv('ADDENDUM_TEST_PG_DSN');
        if ($dsn === false || $dsn === '') {
            self::markTestSkipped('Set ADDENDUM_TEST_PG_DSN to a migrated PostgreSQL test database');
        }

        $pdo = new PDO($dsn, getenv('ADDENDUM_TEST_PG_USER') ?: 'postgres', getenv('ADDENDUM_TEST_PG_PASSWORD') ?: '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->beginTransaction();
        try {
            $repository = new TokenValidationRepository($pdo);
            $keys = JwtKeyPair::shared();
            $users = $this->createStub(AuthRepositoryInterface::class);
            $users->method('findUserByEmail')->willReturn(new UserIdentity(1, 'integration-user', 'user@example.com'));
            $users->method('latestPasswordHash')->willReturn(password_hash('password', PASSWORD_ARGON2ID));
            $admins = $this->createStub(AdminRepositoryInterface::class);
            $admins->method('isUserAdmin')->willReturn(false);
            $service = new AuthService(
                $users, $repository, $admins,
                new JwtConfig($keys->privateKeyPath, $keys->publicKeyPath, null, 3600, 86400),
                new JtiGenerator()
            );
            $phone = $service->login('user@example.com', 'password', 'phone');
            $desktop = $service->login('user@example.com', 'password', 'desktop');
            $renewed = $service->refresh($phone->refreshToken, 'phone');
            $auth = new Auth($keys->publicKeyPath, $repository, $this->createStub(ApplicationTokenValidator::class));
            $action = new DeleteSessionAction($service);
            $logout = new class ($action) implements RequestHandlerInterface {
                public function __construct(private DeleteSessionAction $action) {}
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    ($this->action)(new Request($request));
                    return new Response(204);
                }
            };

            self::assertSame(204, $auth->process($this->request($renewed->accessToken), $logout)->getStatusCode());
            $handler = $this->createStub(RequestHandlerInterface::class);
            $handler->method('handle')->willReturn(new Response(200));
            foreach ([$phone->accessToken, $renewed->accessToken] as $access) {
                self::assertSame(401, $auth->process($this->request($access), $handler)->getStatusCode());
            }
            foreach ([$phone->refreshToken, $renewed->refreshToken] as $refresh) {
                try {
                    $service->refresh($refresh, 'phone');
                    self::fail('Logged-out session was refreshed');
                } catch (UnauthorizedException $exception) {
                    self::assertSame('Refresh token has been revoked', $exception->getMessage());
                }
            }

            self::assertSame(200, $auth->process($this->request($desktop->accessToken), $handler)->getStatusCode());
            $desktopRenewed = $service->refresh($desktop->refreshToken, 'desktop');
            self::assertSame(
                Jwt::decode($desktop->accessToken, $keys->publicKeyPath)->sid,
                Jwt::decode($desktopRenewed->accessToken, $keys->publicKeyPath)->sid
            );
            self::assertSame(401, $auth->process($this->request($desktopRenewed->refreshToken), $handler)->getStatusCode());
        } finally {
            $pdo->rollBack();
        }
    }

    private function request(string $token): ServerRequest
    {
        return (new ServerRequest('DELETE', '/v1/sessions/current'))->withHeader('Authorization', 'Bearer ' . $token);
    }
}
