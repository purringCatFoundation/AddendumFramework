<?php
declare(strict_types=1);

namespace PCF\Addendum\Entity\User;

final readonly class ApplicationTokenStatistics
{
    public function __construct(
        public int $totalTokens,
        public int $activeTokens,
        public int $revokedTokens,
        public int $uniqueApplications,
        public int $uniqueOwners,
        public int $tokensUsedLast24Hours,
        public int $tokensUsedLast7Days
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            totalTokens: (int) $row['total_tokens'],
            activeTokens: (int) $row['active_tokens'],
            revokedTokens: (int) $row['revoked_tokens'],
            uniqueApplications: (int) $row['unique_applications'],
            uniqueOwners: (int) $row['unique_owners'],
            tokensUsedLast24Hours: (int) $row['tokens_used_last_24h'],
            tokensUsedLast7Days: (int) $row['tokens_used_last_7d'],
        );
    }
}
