<?php
declare(strict_types=1);

namespace PCF\Addendum\Attribute;

use Ds\Vector;

/**
 * Marker interface for attributes that require specific middleware
 *
 * Attributes implementing this interface will automatically
 * trigger their associated middleware without explicit declaration.
 */
interface RequiresMiddleware
{
    /**
     * Get the middleware class(es) required by this attribute
     *
     * @return Vector<class-string>
     */
    public function getRequiredMiddleware(): Vector;
}
