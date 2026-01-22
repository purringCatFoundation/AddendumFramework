<?php
declare(strict_types=1);

namespace PCF\Addendum\Response;

use JsonSerializable;

/**
 * Wrapper response for 201 Created operations
 *
 * Wraps any JsonSerializable response and returns 201 status code.
 * Used for POST operations that create new resources.
 */
final readonly class CreatedResponse implements JsonSerializable, HttpStatusAware
{
    public function __construct(private JsonSerializable $wrapped)
    {
    }

    public function getStatusCode(): int
    {
        return 201;
    }

    public function jsonSerialize(): mixed
    {
        return $this->wrapped->jsonSerialize();
    }
}
