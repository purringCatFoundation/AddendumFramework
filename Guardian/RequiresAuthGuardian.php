<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Guardian;

use Pradzikowski\Framework\Guardian\AccessControlGuardianInterface;
use Pradzikowski\Framework\Auth\Session;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Simple guardian that checks if user is authenticated
 *
 * This guardian simply verifies that a valid session exists.
 * Since the AccessControl middleware only runs if Auth middleware
 * has already validated the session, this guardian always passes.
 *
 * Usage:
 * #[AccessControl(RequiresAuthGuardian::class)]
 * class GetUserAction { }
 *
 * Note: This is useful for documenting that an action requires authentication,
 * but you can also just use #[Middleware(Auth::class)] directly.
 */
class RequiresAuthGuardian implements AccessControlGuardianInterface
{
    /**
     * {@inheritdoc}
     *
     * Always returns true because if we got here, the user is authenticated
     * (Auth middleware already validated the session)
     */
    public function authorize(ServerRequestInterface $request, Session $session): bool
    {
        // If we got here with a valid session, authorization passes
        return true;
    }
}
