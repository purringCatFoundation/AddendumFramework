<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Auth;

use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Auth\Session;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenType;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $session = new Session(
            userUuid: 'user-uuid-123',
            tokenType: TokenType::USER,
            tokenIssuedAt: 1234567800,
            tokenExpiresAt: 1234567890,
            tokenId: 'jti-123'
        );

        $this->assertSame('user-uuid-123', $session->userUuid);
        $this->assertSame(TokenType::USER, $session->tokenType);
        $this->assertSame(1234567800, $session->tokenIssuedAt);
        $this->assertSame(1234567890, $session->tokenExpiresAt);
        $this->assertSame('jti-123', $session->tokenId);
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $session = new Session(userUuid: 'user-uuid-123', tokenType: TokenType::USER);

        $this->assertSame('user-uuid-123', $session->userUuid);
        $this->assertSame(TokenType::USER, $session->tokenType);
        $this->assertNull($session->tokenIssuedAt);
        $this->assertNull($session->tokenExpiresAt);
        $this->assertNull($session->tokenId);
    }

    public function testHasElevatedPrivileges(): void
    {
        $this->assertTrue(new Session('user-uuid', TokenType::ADMIN)->hasElevatedPrivileges());
        $this->assertTrue(new Session('user-uuid', TokenType::APPLICATION)->hasElevatedPrivileges());
        $this->assertFalse(new Session('user-uuid', TokenType::USER)->hasElevatedPrivileges());
        $this->assertFalse(new Session('user-uuid', 'workspace_member')->hasElevatedPrivileges());
    }

    public function testRequiresOwnershipValidation(): void
    {
        $this->assertTrue(new Session('user-uuid', TokenType::USER)->requiresOwnershipValidation());
        $this->assertTrue(new Session('user-uuid', 'workspace_member')->requiresOwnershipValidation());
        $this->assertFalse(new Session('user-uuid', TokenType::ADMIN)->requiresOwnershipValidation());
    }

    public function testTypeChecks(): void
    {
        $adminSession = new Session(userUuid: 'user-uuid', tokenType: TokenType::ADMIN);
        $appSession = new Session(userUuid: 'user-uuid', tokenType: TokenType::APPLICATION);
        $userSession = new Session(userUuid: 'user-uuid', tokenType: TokenType::USER);
        $customSession = new Session(userUuid: 'user-uuid', tokenType: 'workspace_member');

        $this->assertTrue($adminSession->isAdmin());
        $this->assertFalse($userSession->isAdmin());
        $this->assertTrue($appSession->isApplication());
        $this->assertFalse($userSession->isApplication());
        $this->assertTrue($userSession->isUser());
        $this->assertFalse($adminSession->isUser());
        $this->assertFalse($customSession->isUser());
    }

    public function testFromTokenPayload(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: 1234567890,
            jti: 'jti-123',
            iat: 1234567800,
            tokenType: 'workspace_member'
        );

        $session = Session::fromTokenPayload($payload);

        $this->assertSame('user-uuid-123', $session->userUuid);
        $this->assertSame('workspace_member', $session->tokenType);
        $this->assertSame(1234567800, $session->tokenIssuedAt);
        $this->assertSame(1234567890, $session->tokenExpiresAt);
        $this->assertSame('jti-123', $session->tokenId);
    }

    public function testFromRequestWithTokenPayloadAttribute(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: 1234567890,
            jti: 'jti-123',
            iat: 1234567800,
            tokenType: TokenType::USER
        );

        $request = (new ServerRequest('GET', '/test'))->withAttribute('token_payload', $payload);

        $session = Session::fromRequest($request);

        $this->assertSame('user-uuid-123', $session->userUuid);
        $this->assertSame(TokenType::USER, $session->tokenType);
    }

    public function testFromRequestWithIndividualAttributes(): void
    {
        $request = (new ServerRequest('GET', '/test'))
            ->withAttribute('user_uuid', 'user-uuid-123')
            ->withAttribute('token_type', 'workspace_member')
            ->withAttribute('token_issued_at', 1234567800)
            ->withAttribute('token_expires_at', 1234567890)
            ->withAttribute('token_id', 'jti-123');

        $session = Session::fromRequest($request);

        $this->assertSame('user-uuid-123', $session->userUuid);
        $this->assertSame('workspace_member', $session->tokenType);
    }

    public function testFromRequestDefaultsToUserTokenType(): void
    {
        $request = (new ServerRequest('GET', '/test'))->withAttribute('user_uuid', 'user-uuid-123');

        $session = Session::fromRequest($request);

        $this->assertSame(TokenType::USER, $session->tokenType);
    }

    public function testCanBypassOwnership(): void
    {
        $this->assertTrue(new Session(userUuid: 'user-uuid', tokenType: TokenType::ADMIN)->canBypassOwnership());
        $this->assertFalse(new Session(userUuid: 'user-uuid', tokenType: TokenType::USER)->canBypassOwnership());
    }

    public function testToArray(): void
    {
        $session = new Session(
            userUuid: 'user-uuid-123',
            tokenType: TokenType::ADMIN,
            tokenIssuedAt: 1234567800,
            tokenExpiresAt: 1234567890,
            tokenId: 'jti-123'
        );

        $array = $session->toArray();

        $this->assertSame('user-uuid-123', $array['userUuid']);
        $this->assertSame('admin', $array['tokenType']);
        $this->assertSame(1234567800, $array['tokenIssuedAt']);
        $this->assertSame(1234567890, $array['tokenExpiresAt']);
        $this->assertSame('jti-123', $array['tokenId']);
        $this->assertTrue($array['hasElevatedPrivileges']);
        $this->assertTrue($array['isAdmin']);
        $this->assertFalse($array['isApplication']);
        $this->assertFalse($array['isUser']);
    }
}
