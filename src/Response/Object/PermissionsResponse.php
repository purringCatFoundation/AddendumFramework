<?php
declare(strict_types=1);

namespace PCF\Addendum\Response\Object;

use Ds\Vector;
use JsonSerializable;

/**
 * Response for permissions list operations
 */
final readonly class PermissionsResponse implements JsonSerializable
{
    /** @var Vector<mixed> */
    private Vector $permissions;

    public function __construct(iterable $permissions)
    {
        $this->permissions = $permissions instanceof Vector ? $permissions->copy() : new Vector($permissions);
    }

    public function jsonSerialize(): array
    {
        return [
            'permissions' => $this->permissions->toArray()
        ];
    }
}
