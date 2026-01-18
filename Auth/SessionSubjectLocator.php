<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Auth;

use MakinaCorpus\AccessControl\SubjectLocator\SubjectLocator;

/**
 * Subject locator for makinacorpus/access-control
 *
 * Locates the current subject (Session) from the request context.
 * This is used by makinacorpus Authorization to find the current user.
 *
 * Note: The subject is stored in a static property because makinacorpus
 * doesn't have a built-in PSR-7 request integration. We set it in the
 * MakinaAccessControl middleware.
 */
class SessionSubjectLocator implements SubjectLocator
{
    private static ?Session $currentSession = null;

    /**
     * Set the current session (called by middleware)
     */
    public static function setCurrentSession(?Session $session): void
    {
        self::$currentSession = $session;
    }

    /**
     * Get the current session
     */
    public static function getCurrentSession(): ?Session
    {
        return self::$currentSession;
    }

    /**
     * {@inheritdoc}
     *
     * Returns the current Session or null if not authenticated
     */
    public function getSubject()
    {
        return self::$currentSession;
    }
}
