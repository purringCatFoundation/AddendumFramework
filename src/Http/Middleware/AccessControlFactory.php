<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Http\Middleware\AccessControl;
use PCF\Addendum\Http\Middleware\MiddlewareFactoryInterface;
use PCF\Addendum\Http\MiddlewareOptions;
use InvalidArgumentException;

class AccessControlFactory implements MiddlewareFactoryInterface
{
    /**
     * Create AccessControl middleware
     *
     * @param MiddlewareOptions $options Middleware options containing compiled guardians
     */
    public function create(MiddlewareOptions $options): AccessControl
    {
        $guardians = $options->get('accessControlGuardians', AccessControlGuardianCollection::empty());

        if (!$guardians instanceof AccessControlGuardianCollection) {
            throw new InvalidArgumentException('Access control guardians must be an AccessControlGuardianCollection');
        }

        return new AccessControl(
            compiledGuardians: $guardians
        );
    }
}
