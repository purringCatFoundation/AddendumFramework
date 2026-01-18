<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Attribute;

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
     * @return array<class-string> Array of middleware class names
     */
    public function getRequiredMiddleware(): array;
}
