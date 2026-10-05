<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

use DateTimeInterface;
use PDO;

class TokenValidationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function isTokenValid(
        string $tokenType,
        string $subject,
        string $jti,
        int $issuedAt,
        ?string $sessionId = null
    ): bool {
        $stmt = $this->pdo->prepare(
            'SELECT is_token_valid(:token_type, :subject, :jti, to_timestamp(:issued_at)::timestamp, :sid)'
        );
        $stmt->execute([
            'token_type' => $tokenType,
            'subject' => $subject,
            'jti' => $jti,
            'issued_at' => $issuedAt,
            'sid' => $sessionId
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function revokeSession(
        string $sessionId,
        string $subject,
        string $reason = 'user_logout',
        ?string $createdBy = null
    ): void {
        $stmt = $this->pdo->prepare('SELECT revoke_session(:sid, :subject, :reason, :created_by::uuid)');
        $stmt->execute([
            'sid' => $sessionId,
            'subject' => $subject,
            'reason' => $reason,
            'created_by' => $createdBy,
        ]);
    }

    public function revokeToken(
        string $jti,
        string $tokenType,
        string $subject,
        int $issuedAt,
        string $reason = 'token_revocation',
        ?string $createdBy = null
    ): void {
        $stmt = $this->pdo->prepare('SELECT revoke_token(:jti, :token_type, :subject, to_timestamp(:issued_at)::timestamp, :reason, :created_by::uuid)');
        $stmt->execute([
            'jti' => $jti,
            'token_type' => $tokenType,
            'subject' => $subject,
            'issued_at' => $issuedAt,
            'reason' => $reason,
            'created_by' => $createdBy
        ]);
    }

    public function revokeTokensBefore(
        string $tokenType,
        ?string $subject,
        DateTimeInterface $revokedBefore,
        ?string $jti = null,
        string $reason = 'token_revocation',
        ?string $createdBy = null
    ): void {
        $stmt = $this->pdo->prepare('SELECT revoke_tokens_before(:token_type, :subject, :jti, :revoked_before, :reason, :created_by::uuid)');
        $stmt->execute([
            'token_type' => $tokenType,
            'subject' => $subject,
            'jti' => $jti,
            'revoked_before' => $revokedBefore->format('Y-m-d H:i:s'),
            'reason' => $reason,
            'created_by' => $createdBy
        ]);
    }

    public function revokeUserTokens(
        string $userUuid,
        string $reason = 'user_logout',
        ?string $createdBy = null,
        ?string $tokenType = null
    ): void
    {
        $stmt = $this->pdo->prepare('SELECT revoke_user_tokens(:user_uuid::uuid, :reason, :created_by::uuid, :token_type)');
        $stmt->execute([
            'user_uuid' => $userUuid,
            'reason' => $reason,
            'created_by' => $createdBy,
            'token_type' => $tokenType
        ]);
    }

    public function revokeAllTokens(
        string $reason = 'global_revocation',
        ?string $createdBy = null,
        ?string $tokenType = null
    ): void
    {
        $stmt = $this->pdo->prepare('SELECT revoke_all_tokens(:reason, :created_by, :token_type)');
        $stmt->execute([
            'reason' => $reason,
            'created_by' => $createdBy,
            'token_type' => $tokenType
        ]);
    }

    public function cleanupExpiredRevocations(int $daysOld = 30): int
    {
        $stmt = $this->pdo->prepare('SELECT cleanup_expired_revocations(:days_old)');
        $stmt->execute(['days_old' => $daysOld]);
        
        return (int) $stmt->fetchColumn();
    }
}
