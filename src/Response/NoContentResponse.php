<?php
declare(strict_types=1);

namespace PCF\Addendum\Response;

use JsonSerializable;

/**
 * Response for operations that return 204 No Content
 *
 * Used for DELETE operations and other actions that succeed
 * but have no body to return.
 */
final readonly class NoContentResponse implements JsonSerializable, HttpStatusAware
{
    public function getStatusCode(): int
    {
        return 204;
    }

    public function jsonSerialize(): array
    {
        return [];
    }
}
