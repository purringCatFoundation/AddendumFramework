<?php

declare(strict_types=1);

namespace PCF\Addendum\Entity\User;

use DateTimeImmutable;

/** Usage and revocation metadata carried together when copying an application token. */
final readonly class ApplicationTokenState
{
    public function __construct(
        public ?DateTimeImmutable $lastUsedAt = null,
        public ?DateTimeImmutable $revokedAt = null,
        public ?string $revokedReason = null
    ) {
    }
}
