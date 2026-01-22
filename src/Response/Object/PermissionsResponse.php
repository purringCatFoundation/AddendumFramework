<?php
declare(strict_types=1);

namespace PCF\Addendum\Response\Object;

use JsonSerializable;

/**
 * Response for permissions list operations
 */
final readonly class PermissionsResponse implements JsonSerializable
{
    public function __construct(private array $permissions)
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'permissions' => $this->permissions
        ];
    }
}