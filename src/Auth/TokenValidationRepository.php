<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

use PDO;

class TokenValidationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function isTokenValid(string $userUuid, int $issuedAt): bool
    {
        $stmt = $this->pdo->prepare('SELECT is_token_valid(:user_uuid::uuid, to_timestamp(:issued_at)::timestamp)');
        $stmt->execute([
            'user_uuid' => $userUuid,
            'issued_at' => $issuedAt
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function revokeUserTokens(string $userUuid, string $reason = 'user_logout', ?string $createdBy = null): void
    {
        $stmt = $this->pdo->prepare('SELECT revoke_user_tokens(:user_uuid::uuid, :reason, :created_by::uuid)');
        $stmt->execute([
            'user_uuid' => $userUuid,
            'reason' => $reason,
            'created_by' => $createdBy
        ]);
    }

    public function revokeAllTokens(string $reason = 'global_revocation', ?string $createdBy = null): void
    {
        $stmt = $this->pdo->prepare('SELECT revoke_all_tokens(:reason, :created_by)');
        $stmt->execute([
            'reason' => $reason,
            'created_by' => $createdBy
        ]);
    }

    public function cleanupExpiredRevocations(int $daysOld = 30): int
    {
        $stmt = $this->pdo->prepare('SELECT cleanup_expired_revocations(:days_old)');
        $stmt->execute(['days_old' => $daysOld]);
        
        return (int) $stmt->fetchColumn();
    }
}