<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Auth\MakinaPermissionChecker;
use PCF\Addendum\Auth\MakinaRoleChecker;
use PCF\Addendum\Auth\SessionSubjectLocator;
use PCF\Addendum\Http\Middleware\FactoryInterface;
use PCF\Addendum\Http\Middleware\MakinaAccessControl;
use Pradzikowski\Game\Repository\Object\PermissionRepositoryInterface;
use MakinaCorpus\AccessControl\Authorization;
use MakinaCorpus\AccessControl\Bridge\Standalone\StandaloneAuthorizationFactory;
use Psr\Log\LoggerInterface;

class MakinaAccessControlFactory implements FactoryInterface
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepository,
        private readonly ?LoggerInterface $logger = null
    ) {}

    public function create(): MakinaAccessControl
    {
        // Create makinacorpus Authorization instance
        $factory = new StandaloneAuthorizationFactory();

        // Register Role Checker
        $factory->setRoleChecker(new MakinaRoleChecker());

        // Register Permission Checker
        $factory->setPermissionChecker(
            new MakinaPermissionChecker($this->permissionRepository)
        );

        // Register Subject Locator
        $factory->setSubjectLocator(new SessionSubjectLocator());

        // Create Authorization
        $authorization = $factory->create();

        return new MakinaAccessControl($authorization, $this->logger);
    }
}
