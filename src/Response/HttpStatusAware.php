<?php
declare(strict_types=1);

namespace PCF\Addendum\Response;

/**
 * Interface for responses that specify their HTTP status code
 *
 * Implementing this interface allows response objects to return
 * appropriate HTTP status codes (201 Created, 204 No Content, etc.)
 * instead of the default 200 OK.
 */
interface HttpStatusAware
{
    /**
     * Get the HTTP status code for this response
     *
     * @return int HTTP status code (e.g., 200, 201, 204)
     */
    public function getStatusCode(): int;
}
