<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Auth;

use PCF\Addendum\Auth\Session;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenType;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $session = new Session(
            userUuid: 'user-uuid-123',
            tokenType: TokenType::USER,
            characterUuid: 'char-uuid-456',
            tokenIssuedAt: 1234567800,
            tokenExpiresAt: 1234567890,
            tokenId: 'jti-123'
        );

        $this->assertSame('user-uuid-123', $session->userUuid);
        $this->assertSame(TokenType::USER, $session->tokenType);
        $this->assertSame('char-uuid-456', $session->characterUuid);
        $this->assertSame(1234567800, $session->tokenIssuedAt);
        $this->assertSame(1234567890, $session->tokenExpiresAt);
        $this->assertSame('jti-123', $session->tokenId);
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $session = new Session(
            userUuid: 'user-uuid-123',
            tokenType: TokenType::USER
        );

        $this->assertSame('user-uuid-123', $session->userUuid);
        $this->assertSame(TokenType::USER, $session->tokenType);
        $this->assertNull($session->characterUuid);
        $this->assertNull($session->tokenIssuedAt);
        $this->assertNull($session->tokenExpiresAt);
        $this->assertNull($session->tokenId);
    }

    public function testHasElevatedPrivilegesPropertyHookForAdmin(): void
    {
        $session = new Session(
            userUuid: 'user-uuid-123',
            tokenType: TokenType::ADMIN
        );

        $this->assertTrue($session->hasElevatedPrivileges);
    }

    public function testHasElevatedPrivilegesPropertyHookForApplication(): void
    {
        $session = new Session(
            userUuid: 'user-uuid-123',
            tokenType: TokenType::APPLICATION
        );

        $this->assertTrue($session->hasElevatedPrivileges);
    }

    public function testHasElevatedPrivilegesPropertyHookForUser(): void
    {
        $session = new Session(
            userUuid: 'user-uuid-123',
            tokenType: TokenType::USER
        );

        $this->assertFalse($session->hasElevatedPrivileges);
    }

    public function testRequiresOwnershipValidationPropertyHook(): void
    {
        $userSession = new Session(userUuid: 'user-uuid', tokenType: TokenType::USER);
        $adminSession = new Session(userUuid: 'user-uuid', tokenType: TokenType::ADMIN);

        $this->assertTrue($userSession->requiresOwnershipValidation);
        $this->assertFalse($adminSession->requiresOwnershipValidation);
    }

    public function testIsAdminPropertyHook(): void
    {
        $adminSession = new Session(userUuid: 'user-uuid', tokenType: TokenType::ADMIN);
        $userSession = new Session(userUuid: 'user-uuid', tokenType: TokenType::USER);

        $this->assertTrue($adminSession->isAdmin);
        $this->assertFalse($userSession->isAdmin);
    }

    public function testIsApplicationPropertyHook(): void
    {
        $appSession = new Session(userUuid: 'user-uuid', tokenType: TokenType::APPLICATION);
        $userSession = new Session(userUuid: 'user-uuid', tokenType: TokenType::USER);

        $this->assertTrue($appSession->isApplication);
        $this->assertFalse($userSession->isApplication);
    }

    public function testIsUserPropertyHook(): void
    {
        $userSession = new Session(userUuid: 'user-uuid', tokenType: TokenType::USER);
        $adminSession = new Session(userUuid: 'user-uuid', tokenType: TokenType::ADMIN);

        $this->assertTrue($userSession->isUser);
        $this->assertFalse($adminSession->isUser);
    }

    public function testIsCharacterPropertyHook(): void
    {
        $charSession = new Session(userUuid: 'user-uuid', tokenType: TokenType::CHARACTER);
        $userSession = new Session(userUuid: 'user-uuid', tokenType: TokenType::USER);

        $this->assertTrue($charSession->isCharacter);
        $this->assertFalse($userSession->isCharacter);
    }

    public function testFromTokenPayload(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: 1234567890,
            jti: 'jti-123',
            iat: 1234567800,
            tokenType: TokenType::CHARACTER,
            characterUuid: 'char-uuid-456'
        );

        $session = Session::fromTokenPayload($payload);

        $this->assertSame('user-uuid-123', $session->userUuid);
        $this->assertSame(TokenType::CHARACTER, $session->tokenType);
        $this->assertSame('char-uuid-456', $session->characterUuid);
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

        $request = new ServerRequest('GET', '/test');
        $request = $request->withAttribute('token_payload', $payload);

        $session = Session::fromRequest($request);

        $this->assertSame('user-uuid-123', $session->userUuid);
        $this->assertSame(TokenType::USER, $session->tokenType);
    }

    public function testFromRequestWithIndividualAttributes(): void
    {
        $request = new ServerRequest('GET', '/test');
        $request = $request
            ->withAttribute('user_uuid', 'user-uuid-123')
            ->withAttribute('token_type', TokenType::ADMIN)
            ->withAttribute('character_uuid', 'char-uuid-456')
            ->withAttribute('token_issued_at', 1234567800)
            ->withAttribute('token_expires_at', 1234567890)
            ->withAttribute('token_id', 'jti-123');

        $session = Session::fromRequest($request);

        $this->assertSame('user-uuid-123', $session->userUuid);
        $this->assertSame(TokenType::ADMIN, $session->tokenType);
        $this->assertSame('char-uuid-456', $session->characterUuid);
    }

    public function testFromRequestDefaultsToUserTokenType(): void
    {
        $request = new ServerRequest('GET', '/test');
        $request = $request->withAttribute('user_uuid', 'user-uuid-123');

        $session = Session::fromRequest($request);

        $this->assertSame(TokenType::USER, $session->tokenType);
    }

    public function testCanBypassOwnership(): void
    {
        $adminSession = new Session(userUuid: 'user-uuid', tokenType: TokenType::ADMIN);
        $userSession = new Session(userUuid: 'user-uuid', tokenType: TokenType::USER);

        $this->assertTrue($adminSession->canBypassOwnership());
        $this->assertFalse($userSession->canBypassOwnership());
    }

    public function testIsCharacterMatch(): void
    {
        $session = new Session(
            userUuid: 'user-uuid',
            tokenType: TokenType::CHARACTER,
            characterUuid: 'char-uuid-123'
        );

        $this->assertTrue($session->isCharacterMatch('char-uuid-123'));
        $this->assertFalse($session->isCharacterMatch('other-char-uuid'));
    }

    public function testIsCharacterMatchWithNullCharacterUuid(): void
    {
        $session = new Session(
            userUuid: 'user-uuid',
            tokenType: TokenType::USER
        );

        $this->assertFalse($session->isCharacterMatch('any-uuid'));
    }

    public function testToArray(): void
    {
        $session = new Session(
            userUuid: 'user-uuid-123',
            tokenType: TokenType::ADMIN,
            characterUuid: 'char-uuid-456',
            tokenIssuedAt: 1234567800,
            tokenExpiresAt: 1234567890,
            tokenId: 'jti-123'
        );

        $array = $session->toArray();

        $this->assertSame('user-uuid-123', $array['userUuid']);
        $this->assertSame('admin', $array['tokenType']);
        $this->assertSame('char-uuid-456', $array['characterUuid']);
        $this->assertSame(1234567800, $array['tokenIssuedAt']);
        $this->assertSame(1234567890, $array['tokenExpiresAt']);
        $this->assertSame('jti-123', $array['tokenId']);
        $this->assertTrue($array['hasElevatedPrivileges']);
        $this->assertTrue($array['isAdmin']);
        $this->assertFalse($array['isApplication']);
        $this->assertFalse($array['isUser']);
        $this->assertFalse($array['isCharacter']);
    }
}
