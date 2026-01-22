<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Auth;

use PCF\Addendum\Auth\TokenValidationRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TokenValidationRepositoryTest extends TestCase
{
    private PDO&MockObject $mockPdo;
    private PDOStatement&MockObject $mockStatement;
    private TokenValidationRepository $repository;

    protected function setUp(): void
    {
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStatement = $this->createMock(PDOStatement::class);
        $this->repository = new TokenValidationRepository($this->mockPdo);
    }

    public function testIsTokenValidReturnsTrue(): void
    {
        $userUuid = '123e4567-e89b-12d3-a456-426614174000';
        $issuedAt = time();

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->with('SELECT is_token_valid(:user_uuid::uuid, to_timestamp(:issued_at)::timestamp)')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with([
                'user_uuid' => $userUuid,
                'issued_at' => $issuedAt
            ]);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(true);

        $result = $this->repository->isTokenValid($userUuid, $issuedAt);

        $this->assertTrue($result);
    }

    public function testIsTokenValidReturnsFalse(): void
    {
        $userUuid = '123e4567-e89b-12d3-a456-426614174000';
        $issuedAt = time();

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->with('SELECT is_token_valid(:user_uuid::uuid, to_timestamp(:issued_at)::timestamp)')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with([
                'user_uuid' => $userUuid,
                'issued_at' => $issuedAt
            ]);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(false);

        $result = $this->repository->isTokenValid($userUuid, $issuedAt);

        $this->assertFalse($result);
    }

    public function testRevokeUserTokens(): void
    {
        $userUuid = '123e4567-e89b-12d3-a456-426614174000';
        $reason = 'user_logout';
        $createdBy = '456e7890-e89b-12d3-a456-426614174001';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->with('SELECT revoke_user_tokens(:user_uuid::uuid, :reason, :created_by::uuid)')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with([
                'user_uuid' => $userUuid,
                'reason' => $reason,
                'created_by' => $createdBy
            ]);

        $this->repository->revokeUserTokens($userUuid, $reason, $createdBy);
    }

    public function testRevokeUserTokensWithDefaults(): void
    {
        $userUuid = '123e4567-e89b-12d3-a456-426614174000';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->with('SELECT revoke_user_tokens(:user_uuid::uuid, :reason, :created_by::uuid)')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with([
                'user_uuid' => $userUuid,
                'reason' => 'user_logout',
                'created_by' => null
            ]);

        $this->repository->revokeUserTokens($userUuid);
    }

    public function testRevokeAllTokens(): void
    {
        $reason = 'global_revocation';
        $createdBy = '456e7890-e89b-12d3-a456-426614174001';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->with('SELECT revoke_all_tokens(:reason, :created_by)')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with([
                'reason' => $reason,
                'created_by' => $createdBy
            ]);

        $this->repository->revokeAllTokens($reason, $createdBy);
    }

    public function testRevokeAllTokensWithDefaults(): void
    {
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->with('SELECT revoke_all_tokens(:reason, :created_by)')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with([
                'reason' => 'global_revocation',
                'created_by' => null
            ]);

        $this->repository->revokeAllTokens();
    }

    public function testCleanupExpiredRevocations(): void
    {
        $daysOld = 30;
        $deletedCount = 5;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->with('SELECT cleanup_expired_revocations(:days_old)')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with(['days_old' => $daysOld]);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($deletedCount);

        $result = $this->repository->cleanupExpiredRevocations($daysOld);

        $this->assertSame($deletedCount, $result);
    }

    public function testCleanupExpiredRevocationsWithDefault(): void
    {
        $deletedCount = 3;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->with('SELECT cleanup_expired_revocations(:days_old)')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with(['days_old' => 30]);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($deletedCount);

        $result = $this->repository->cleanupExpiredRevocations();

        $this->assertSame($deletedCount, $result);
    }
}