<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

use PCF\Addendum\Action\FactoryInterface;
use PCF\Addendum\Config\JwtConfigFactory;
use PCF\Addendum\Config\SystemEnvironmentProvider;
use PCF\Addendum\Database\DbConnectionFactory;
use PCF\Addendum\Repository\User\AdminRepositoryFactory;
use PCF\Addendum\Repository\User\AuthRepositoryFactory;

class AuthServiceFactory implements FactoryInterface
{
    public function __construct(
        private AuthRepositoryFactory $authRepositoryFactory,
        private TokenValidationRepositoryFactory $tokenValidationRepositoryFactory,
        private AdminRepositoryFactory $adminRepositoryFactory,
        private JwtConfigFactory $jwtConfigFactory,
        private JtiGeneratorFactory $jtiGeneratorFactory
    ) {
    }

    public static function fromEnvironment(): self
    {
        $dbConnectionFactory = new DbConnectionFactory();

        return new self(
            new AuthRepositoryFactory($dbConnectionFactory),
            new TokenValidationRepositoryFactory($dbConnectionFactory),
            new AdminRepositoryFactory(),
            new JwtConfigFactory(new SystemEnvironmentProvider()),
            new JtiGeneratorFactory()
        );
    }

    public function create(): AuthService
    {
        $repository = $this->authRepositoryFactory->create();
        $tokenValidationRepository = $this->tokenValidationRepositoryFactory->create();
        $adminRepository = $this->adminRepositoryFactory->create();
        $jwtConfig = $this->jwtConfigFactory->create();
        $jtiGenerator = $this->jtiGeneratorFactory->create();

        return new AuthService(
            $repository,
            $tokenValidationRepository,
            $adminRepository,
            $jwtConfig,
            $jtiGenerator
        );
    }
}
