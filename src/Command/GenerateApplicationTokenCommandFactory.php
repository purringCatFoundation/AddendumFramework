<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Config\JwtConfigFactory;
use PCF\Addendum\Config\SystemEnvironmentProvider;
use PCF\Addendum\Repository\User\ApplicationTokenRepositoryFactory;

final class GenerateApplicationTokenCommandFactory
{
    public function create(): GenerateApplicationTokenCommand
    {
        $tokenRepositoryFactory = new ApplicationTokenRepositoryFactory();
        $tokenRepository = $tokenRepositoryFactory->create();

        $jwtConfigFactory = new JwtConfigFactory(new SystemEnvironmentProvider());
        $jwtConfig = $jwtConfigFactory->create();

        return new GenerateApplicationTokenCommand($tokenRepository, $jwtConfig);
    }
}
