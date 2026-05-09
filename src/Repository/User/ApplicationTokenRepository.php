<?php
declare(strict_types=1);

namespace PCF\Addendum\Repository\User;

use PCF\Addendum\Entity\User\ApplicationToken;
use PCF\Addendum\Entity\User\ApplicationTokenCollection;
use PCF\Addendum\Entity\User\ApplicationTokenStatistics;
use DateTimeImmutable;
use PDO;

/**
 * Repository for ApplicationToken database operations
 */
final class ApplicationTokenRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function createToken(
        string $tokenHash,
        string $applicationName,
        string $ownerName,
        string $ownerEmail,
        string $jti
    ): ApplicationToken {
        $stmt = $this->db->prepare(
            'SELECT create_application_token(:token_hash, :application_name, :owner_name, :owner_email, :jti) as uuid'
        );

        $stmt->execute([
            ':token_hash' => $tokenHash,
            ':application_name' => $applicationName,
            ':owner_name' => $ownerName,
            ':owner_email' => $ownerEmail,
            ':jti' => $jti,
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $uuid = $result['uuid'];

        $token = $this->getTokenByUuid($uuid);

        if ($token === null) {
            throw new \RuntimeException('Failed to create application token');
        }

        return $token;
    }

    public function getTokenByJti(string $jti): ?ApplicationToken
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM application_tokens WHERE jti = :jti'
        );

        $stmt->execute([':jti' => $jti]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return ApplicationToken::fromDatabaseRow($row);
    }

    public function getTokenByUuid(string $uuid): ?ApplicationToken
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM application_tokens WHERE uuid = :uuid'
        );

        $stmt->execute([':uuid' => $uuid]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return ApplicationToken::fromDatabaseRow($row);
    }

    public function isTokenValid(string $jti): bool
    {
        $stmt = $this->db->prepare(
            'SELECT is_application_token_valid(:jti) as is_valid'
        );

        $stmt->execute([':jti' => $jti]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (bool) $result['is_valid'];
    }

    public function updateLastUsed(string $jti): void
    {
        $stmt = $this->db->prepare(
            'SELECT update_application_token_last_used(:jti)'
        );

        $stmt->execute([':jti' => $jti]);
    }

    public function revokeToken(string $jti, ?string $reason = null): bool
    {
        $stmt = $this->db->prepare(
            'SELECT revoke_application_token(:jti, :reason) as revoked'
        );

        $stmt->execute([
            ':jti' => $jti,
            ':reason' => $reason,
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (bool) $result['revoked'];
    }

    public function revokeByApplicationName(
        string $applicationName,
        ?DateTimeImmutable $createdAfter = null,
        ?string $reason = null
    ): int {
        $stmt = $this->db->prepare(
            'SELECT revoke_application_tokens_by_name(:application_name, :created_after, :reason) as count'
        );

        $stmt->execute([
            ':application_name' => $applicationName,
            ':created_after' => $createdAfter?->format('Y-m-d H:i:s'),
            ':reason' => $reason,
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $result['count'];
    }

    public function revokeByOwner(
        string $ownerEmail,
        ?DateTimeImmutable $createdAfter = null,
        ?string $reason = null
    ): int {
        $stmt = $this->db->prepare(
            'SELECT revoke_application_tokens_by_owner(:owner_email, :created_after, :reason) as count'
        );

        $stmt->execute([
            ':owner_email' => $ownerEmail,
            ':created_after' => $createdAfter?->format('Y-m-d H:i:s'),
            ':reason' => $reason,
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $result['count'];
    }

    public function revokeByDate(
        DateTimeImmutable $createdAfter,
        ?string $applicationName = null,
        ?string $ownerEmail = null,
        ?string $reason = null
    ): int {
        $stmt = $this->db->prepare(
            'SELECT revoke_application_tokens_by_date(:created_after, :application_name, :owner_email, :reason) as count'
        );

        $stmt->execute([
            ':created_after' => $createdAfter->format('Y-m-d H:i:s'),
            ':application_name' => $applicationName,
            ':owner_email' => $ownerEmail,
            ':reason' => $reason,
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $result['count'];
    }

    public function listActiveTokens(): ApplicationTokenCollection
    {
        $stmt = $this->db->query(
            'SELECT * FROM list_active_application_tokens()'
        );

        return $this->tokensFromRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getTokensByApplication(string $applicationName): ApplicationTokenCollection
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM application_tokens WHERE application_name = :application_name ORDER BY created_at DESC'
        );

        $stmt->execute([':application_name' => $applicationName]);

        return $this->tokensFromRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getTokensByOwner(string $ownerEmail): ApplicationTokenCollection
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM application_tokens WHERE owner_email = :owner_email ORDER BY created_at DESC'
        );

        $stmt->execute([':owner_email' => $ownerEmail]);

        return $this->tokensFromRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getStatistics(): ApplicationTokenStatistics
    {
        $stmt = $this->db->query(
            'SELECT * FROM get_application_token_statistics()'
        );

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return ApplicationTokenStatistics::fromDatabaseRow($result);
    }

    private function tokensFromRows(iterable $rows): ApplicationTokenCollection
    {
        $tokens = new ApplicationTokenCollection();

        foreach ($rows as $row) {
            $tokens->add(ApplicationToken::fromDatabaseRow($row));
        }

        return $tokens;
    }
}
