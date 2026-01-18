<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Middleware;

use Pradzikowski\Framework\Auth\MakinaPermissionChecker;
use Pradzikowski\Framework\Auth\MakinaRoleChecker;
use Pradzikowski\Framework\Auth\SessionSubjectLocator;
use Pradzikowski\Framework\Http\Middleware\FactoryInterface;
use Pradzikowski\Framework\Http\Middleware\MakinaAccessControl;
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
